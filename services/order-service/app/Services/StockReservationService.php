<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Servicio de reserva de inventario con doble capa: Redis (alta velocidad) y base de datos MySQL.
 *
 * Este servicio utiliza scripts Lua para garantizar atomicidad en las operaciones de stock sobre Redis,
 * evitando condiciones de carrera en entornos concurrentes (colas, múltiples workers, etc.).
 * Las reservas se reflejan tanto en Redis (TTL corto) como en la tabla `stock_reservations`
 * de la base de datos, permitiendo recuperación ante fallos y reconciliación periódica.
 */
class StockReservationService
{
    /** La reserva está activa y pendiente de confirmación. */
    private const RESERVATION_STATUS_RESERVED = 'reserved';

    /** La reserva fue confirmada exitosamente (stock descontado permanentemente). */
    private const RESERVATION_STATUS_CONFIRMED = 'confirmed';

    /** La reserva fue cancelada o expiró, y el stock fue liberado. */
    private const RESERVATION_STATUS_CANCELLED = 'cancelled';

    /** Código HTTP para indicar que no hay suficiente stock (conflicto de negocio). */
    private const OUT_OF_STOCK_HTTP_STATUS = 409;

    /** Código HTTP para indicar contención — otro proceso está usando el recurso. */
    private const CONFLICT_HTTP_STATUS = 429;

    /** Código HTTP para errores de validación (parámetros inválidos). */
    private const VALIDATION_HTTP_STATUS = 422;

    /** Código HTTP para errores de indisponibilidad del servicio (catalog-service, Redis). */
    private const UNAVAILABLE_HTTP_STATUS = 503;

    /**
     * Caché interna del resultado de verificar la tabla `stock_reservations`.
     * Se consulta una sola vez por ciclo de vida para evitar llamadas repetidas a `Schema::hasTable`.
     */
    private ?bool $reservationTableExists = null;

    /**
     * @param StockReservationRealtimePublisher $realtimePublisher Publicador de eventos en tiempo real (WebSockets/SSE)
     *                                                             para notificar cambios de reserva a la capa frontend.
     */
    public function __construct(
        private readonly StockReservationRealtimePublisher $realtimePublisher,
    ) {}

    /**
     * Reserva inventario para una orden completa.
     *
     * Flujo:
     * 1. Verifica que la tabla `stock_reservations` exista.
     * 2. Normaliza los ítems (agrupa por size_variant_id).
     * 3. Adquiere un lock distribuido por cada variante para evitar condiciones de carrera.
     * 4. Ejecuta `reserveRedisStock` (script Lua atómico) sobre Redis para descontar stock temporalmente.
     * 5. Persiste la reserva en Redis (con TTL) y en la base de datos MySQL.
     * 6. Publica un evento en tiempo real vía `StockReservationRealtimePublisher`.
     * 7. En caso de fallo, revierte las reservas parciales en Redis y libera los locks.
     *
     * @param int    $orderId     ID de la orden en el sistema de pedidos.
     * @param string $orderNumber Número único de orden (visible para el usuario).
     * @param array  $items       Lista de ítems con product_id, size_variant_id y quantity.
     * @param int    $ttlSeconds  Tiempo de vida de la reserva en segundos.
     *
     * @return array{ok: bool, expires_at?: string, ttl_seconds?: int, code?: string, message?: string, http_status?: int}
     */
    public function reserveForOrder(int $orderId, string $orderNumber, array $items, int $ttlSeconds): array
    {
        // Validación temprana: si la tabla no existe, no podemos continuar.
        if (!$this->hasReservationTable()) {
            return $this->failure(
                code: 'reservation_table_missing',
                message: 'No existe la tabla stock_reservations. Ejecuta la migración antes de crear pedidos.',
                httpStatus: 500,
            );
        }

        // Agrupa ítems duplicados por size_variant_id y descarta los que tengan datos inválidos.
        $normalizedItems = $this->normalizeReservationItems($items);
        if ($normalizedItems->isEmpty()) {
            return $this->failure(
                code: 'invalid_items',
                message: 'La orden no tiene ítems válidos para reserva de inventario.',
                httpStatus: self::VALIDATION_HTTP_STATUS,
            );
        }

        // Garantiza un TTL mínimo de 60 segundos para evitar reservas efímeras.
        $ttl = max(60, $ttlSeconds);
        $locks = [];
        $reservedByKey = [];

        try {
            // Inicializa las claves de stock en Redis si aún no existen (consulta a catalog-service).
            foreach ($normalizedItems as $item) {
                $this->ensureStockKeyInitialized((int) $item['size_variant_id']);
            }

            // Ordena las claves para evitar interbloqueos (deadlocks) entre procesos concurrentes.
            $reservationKeys = $normalizedItems
                ->pluck('size_variant_id')
                ->map(static fn ($value): string => (string) $value)
                ->unique()
                ->sort()
                ->values()
                ->all();

            // Adquiere un lock de Redis por cada variante en orden ascendente.
            foreach ($reservationKeys as $reservationKey) {
                /** @var mixed $redisStore */
                $redisStore = Cache::store('redis');
                $lock = $redisStore->lock(
                    $this->reservationLockKey($reservationKey),
                    $this->lockSeconds(),
                );

                // Si no se puede adquirir el lock, otro proceso está modificando el mismo stock.
                if (!$lock instanceof Lock || !$lock->get()) {
                    $this->releaseLocks($locks);

                    return $this->failure(
                        code: 'stock_busy',
                        message: 'El inventario está siendo actualizado. Intenta de nuevo en unos segundos.',
                        httpStatus: self::CONFLICT_HTTP_STATUS,
                    );
                }

                $locks[$reservationKey] = $lock;
            }

            // Ejecuta la reserva atómica en Redis para cada ítem usando un script Lua.
            foreach ($normalizedItems as $item) {
                $reservationKey = (string) $item['size_variant_id'];
                $quantity = (int) $item['quantity'];

                $reserveResult = $this->reserveRedisStock($reservationKey, $quantity);
                // Si alguna variante no tiene suficiente stock, revierte todo lo reservado hasta ahora.
                if (!($reserveResult['ok'] ?? false)) {
                    $this->rollbackRedisReservation($reservedByKey);
                    $this->releaseLocks($locks);

                    return $this->failure(
                        code: $reserveResult['code'] ?? 'out_of_stock',
                        message: $reserveResult['message'] ?? 'No hay stock disponible para uno de los productos del pedido.',
                        httpStatus: self::OUT_OF_STOCK_HTTP_STATUS,
                    );
                }

                // Lleva un conteo acumulado por variante para poder revertir en caso de error.
                $reservedByKey[$reservationKey] = ($reservedByKey[$reservationKey] ?? 0) + $quantity;
            }

            $expiresAt = now()->addSeconds($ttl);

            // Almacena el payload de la reserva en Redis con expiración y en MySQL como filas persistentes.
            $this->storeReservationInRedis($orderId, $orderNumber, $normalizedItems, $ttl, $expiresAt);
            $this->persistReservationRows($orderId, $normalizedItems, $expiresAt);

            // Notifica en tiempo real a los clientes suscritos (WebSockets).
            $this->realtimePublisher->publish('stock.reservation.created', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'expires_at' => $expiresAt->toIso8601String(),
                'items' => $normalizedItems->map(static fn (array $item): array => [
                    'product_id' => (int) $item['product_id'],
                    'size_variant_id' => (int) $item['size_variant_id'],
                    'quantity' => (int) $item['quantity'],
                ])->values()->all(),
            ]);

            return [
                'ok' => true,
                'expires_at' => $expiresAt->toIso8601String(),
                'ttl_seconds' => $ttl,
            ];
        } catch (Throwable $exception) {
            // Reversión total si ocurre cualquier excepción durante el proceso.
            $this->rollbackRedisReservation($reservedByKey);

            Log::error('No se pudo reservar inventario en Redis.', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'error' => $exception->getMessage(),
            ]);

            // Los errores InvalidArgumentException indican que la variante no existe o no es válida.
            if ($exception instanceof \InvalidArgumentException) {
                return $this->failure(
                    code: 'invalid_variant',
                    message: $exception->getMessage(),
                    httpStatus: self::VALIDATION_HTTP_STATUS,
                );
            }

            return $this->failure(
                code: 'redis_unavailable',
                message: 'No pudimos reservar inventario en este momento. Intenta de nuevo en unos minutos.',
                httpStatus: self::UNAVAILABLE_HTTP_STATUS,
            );
        } finally {
            // Libera todos los locks adquiridos, haya funcionado o no la reserva.
            $this->releaseLocks($locks);
        }
    }

    /**
     * Extiende el tiempo de vida de una reserva existente.
     *
     * Se usa cuando el usuario aún está en el proceso de pago y necesita más tiempo.
     * Actualiza tanto la base de datos como el payload en Redis con el nuevo `expires_at`.
     * Si la reserva ya fue confirmada o cancelada, la operación no tiene efecto.
     *
     * @param int $orderId    ID de la orden.
     * @param int $ttlSeconds Nuevos segundos de vida a partir de ahora.
     *
     * @return bool True si se extendió la reserva, false si no había reserva activa.
     */
    public function extendReservation(int $orderId, int $ttlSeconds): bool
    {
        // Si la tabla no existe, no hay nada que extender.
        if (!$this->hasReservationTable()) {
            return false;
        }

        // Garantiza un mínimo de 60 segundos para la extensión.
        $ttl = max(60, $ttlSeconds);

        // Obtiene las filas activas de la reserva (solo estado 'reserved').
        $rows = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->get();

        if ($rows->isEmpty()) {
            return false;
        }

        $candidateExpiry = now()->addSeconds($ttl);
        // Calcula el timestamp más alto entre las filas actuales para no reducir el tiempo de expiración.
        $maxCurrentExpiryTimestamp = $rows
            ->map(static function ($row): int {
                try {
                    if (($row->expires_at ?? null) === null) {
                        return 0;
                    }

                    return Carbon::parse((string) $row->expires_at)->timestamp;
                } catch (Throwable) {
                    return 0;
                }
            })
            ->max();

        // Usa el mayor entre el nuevo candidato y el expiry actual para no acortar la ventana.
        $expiresAt = $candidateExpiry;
        if ($maxCurrentExpiryTimestamp > $candidateExpiry->timestamp) {
            $expiresAt = Carbon::createFromTimestamp($maxCurrentExpiryTimestamp);
        }

        // Calcula el TTL restante para Redis, con piso de 60 segundos.
        $redisTtl = max(60, now()->diffInSeconds($expiresAt, false));

        // Actualiza la base de datos con la nueva fecha de expiración.
        DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->update([
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

        // Refresca el payload en Redis para mantener sincronizados ambos almacenes.
        $this->refreshReservationRedisPayload($orderId, $rows, $redisTtl, $expiresAt);

        // Notifica en tiempo real a los clientes suscritos.
        $this->realtimePublisher->publish('stock.reservation.extended', [
            'order_id' => $orderId,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Confirma la reserva de inventario, descontando el stock de forma permanente en catalog-service.
     *
     * Flujo:
     * 1. Busca filas activas (status=reserved) en `stock_reservations`.
     * 2. Si no hay filas activas pero ya están confirmadas, retorna éxito (idempotencia).
     * 3. Si no hay filas activas ni confirmadas, ejecuta `confirmWithoutReservation` (fallback).
     * 4. Envía el commit al catalog-service vía HTTP para descontar stock definitivamente.
     * 5. Actualiza el status a 'confirmed' en MySQL.
     * 6. Limpia el contador 'reserved' en Redis (vía script Lua).
     * 7. Elimina el payload temporal de la reserva en Redis.
     * 8. Publica evento en tiempo real.
     *
     * @param int $orderId ID de la orden a confirmar.
     *
     * @return array{ok: bool, items?: array, already_confirmed?: bool, fallback_commit?: bool, code?: string, message?: string, http_status?: int}
     */
    public function confirmReservation(int $orderId): array
    {
        if (!$this->hasReservationTable()) {
            return $this->failure(
                code: 'reservation_table_missing',
                message: 'No existe la tabla stock_reservations.',
                httpStatus: 500,
            );
        }

        // Busca filas activas de la reserva para esta orden.
        $rows = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->get();

        if ($rows->isEmpty()) {
            // Idempotencia: si ya fue confirmada, lo reportamos como éxito silencioso.
            $alreadyConfirmed = DB::table('stock_reservations')
                ->where('order_id', $orderId)
                ->where('status', self::RESERVATION_STATUS_CONFIRMED)
                ->exists();

            if ($alreadyConfirmed) {
                return [
                    'ok' => true,
                    'already_confirmed' => true,
                ];
            }

            // Si no hay reserva previa, intenta confirmar directamente desde los ítems de la orden.
            return $this->confirmWithoutReservation($orderId);
        }

        // Prepara los ítems para enviarlos al catalog-service.
        $items = $rows->map(static fn ($row): array => [
            'product_id' => (int) ($row->product_id ?? 0),
            'size_variant_id' => (int) ($row->size_variant_id ?? 0),
            'quantity' => (int) ($row->quantity ?? 0),
        ])->values()->all();

        // Envía el commit al catalog-service (strictReservation=true: exige que exista reserva previa).
        $commitResult = $this->commitInventoryInCatalog($orderId, $items, true);
        if (!($commitResult['ok'] ?? false)) {
            return $commitResult;
        }

        // Marca las filas como confirmadas en la base de datos.
        DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->update([
                'status' => self::RESERVATION_STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]);

        // Agrupa cantidades por reservation_key para limpiar los contadores en Redis.
        $rowsByKey = $rows
            ->groupBy(static fn ($row): string => (string) $row->reservation_key)
            ->map(static fn (Collection $itemsForKey): int => $itemsForKey->sum(static fn ($row): int => (int) $row->quantity));

        // Reduce el contador 'reserved' en Redis según lo confirmado (script Lua).
        foreach ($rowsByKey as $reservationKey => $quantity) {
            $this->confirmRedisReservation((string) $reservationKey, (int) $quantity);
        }

        // Elimina el payload temporal de la reserva en Redis ya que fue confirmada.
        Redis::del($this->reservationRedisKey($orderId));

        $this->realtimePublisher->publish('stock.reservation.confirmed', [
            'order_id' => $orderId,
            'items' => $items,
        ]);

        return [
            'ok' => true,
            'items' => $items,
        ];
    }

    /**
     * Libera (cancela) una reserva de inventario y devuelve el stock a Redis y catalog-service.
     *
     * Flujo:
     * 1. Verifica existencia de la tabla.
     * 2. Obtiene las filas activas (status=reserved) de la orden.
     * 3. Adquiere locks distribuidos sobre cada variante.
     * 4. Ejecuta `releaseRedisStock` (script Lua) para incrementar el stock disponible en Redis.
     * 5. Actualiza el status a 'cancelled' en MySQL.
     * 6. Elimina el payload de la reserva en Redis.
     * 7. Publica evento en tiempo real.
     *
     * @param int         $orderId      ID de la orden.
     * @param string      $targetStatus Estado final de la reserva (siempre se fuerza a 'cancelled').
     * @param string|null $reason       Razón opcional para incluir en el evento publicado.
     *
     * @return array{ok: bool, released?: int, code?: string, message?: string, http_status?: int}
     */
    public function releaseReservation(int $orderId, string $targetStatus = self::RESERVATION_STATUS_CANCELLED, ?string $reason = null): array
    {
        if (!$this->hasReservationTable()) {
            return $this->failure(
                code: 'reservation_table_missing',
                message: 'No existe la tabla stock_reservations.',
                httpStatus: 500,
            );
        }

        // El vencimiento del TTL es una causa operativa; la reserva siempre se cierra como 'cancelled'
        // para no crear estados finales paralelos y simplificar la lógica de negocio.
        $status = self::RESERVATION_STATUS_CANCELLED;

        // Obtiene filas activas agrupadas por reservation_key para liberar el stock proporcional.
        $rows = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->get();

        // Si no hay filas activas, no hay nada que liberar.
        if ($rows->isEmpty()) {
            return [
                'ok' => true,
                'released' => 0,
            ];
        }

        $locks = [];
        // Agrupa por reservation_key y suma cantidades para liberar el stock correcto.
        $releaseByKey = $rows
            ->groupBy(static fn ($row): string => (string) $row->reservation_key)
            ->map(static fn (Collection $itemsForKey): int => $itemsForKey->sum(static fn ($row): int => (int) $row->quantity))
            ->sortKeys();

        try {
            // Adquiere locks en orden ascendente para evitar interbloqueos.
            foreach ($releaseByKey->keys()->all() as $reservationKey) {
                /** @var mixed $redisStore */
                $redisStore = Cache::store('redis');
                $lock = $redisStore->lock(
                    $this->reservationLockKey((string) $reservationKey),
                    $this->lockSeconds(),
                );

                // Si no se puede adquirir el lock, otro proceso está modificando el mismo stock.
                if (!$lock instanceof Lock || !$lock->get()) {
                    $this->releaseLocks($locks);

                    return $this->failure(
                        code: 'stock_busy',
                        message: 'El inventario está ocupado en este momento. Reintenta la liberación.',
                        httpStatus: self::CONFLICT_HTTP_STATUS,
                    );
                }

                $locks[(string) $reservationKey] = $lock;
            }

            // Ejecuta la liberación atómica en Redis para cada variante (script Lua: INCRBY stock, DECRBY reserved).
            foreach ($releaseByKey as $reservationKey => $quantity) {
                $releaseResult = $this->releaseRedisStock((string) $reservationKey, (int) $quantity);
                if (!($releaseResult['ok'] ?? false)) {
                    return $releaseResult;
                }
            }

            // Marca las filas como canceladas en la base de datos.
            DB::table('stock_reservations')
                ->where('order_id', $orderId)
                ->where('status', self::RESERVATION_STATUS_RESERVED)
                ->update([
                    'status' => $status,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            // Elimina el payload temporal de la reserva en Redis.
            Redis::del($this->reservationRedisKey($orderId));

            $this->realtimePublisher->publish('stock.reservation.released', [
                'order_id' => $orderId,
                'status' => $status,
                'reason' => $reason,
                'items' => $rows->map(static fn ($row): array => [
                    'product_id' => (int) ($row->product_id ?? 0),
                    'size_variant_id' => (int) ($row->size_variant_id ?? 0),
                    'quantity' => (int) ($row->quantity ?? 0),
                ])->values()->all(),
            ]);

            return [
                'ok' => true,
                'released' => (int) $rows->count(),
            ];
        } catch (Throwable $exception) {
            Log::error('Falló la liberación de reserva de inventario.', [
                'order_id' => $orderId,
                'target_status' => $status,
                'error' => $exception->getMessage(),
            ]);

            return $this->failure(
                code: 'release_failed',
                message: 'No fue posible liberar la reserva de inventario.',
                httpStatus: self::UNAVAILABLE_HTTP_STATUS,
            );
        } finally {
            $this->releaseLocks($locks);
        }
    }

    /**
     * Marca una reserva como expirada por TTL.
     *
     * Delega en `releaseReservation` con la razón 'reservation_ttl_expired'.
     * Esto ocurre cuando el usuario no completa el pago dentro de la ventana de tiempo.
     *
     * @param int $orderId ID de la orden cuya reserva expiró.
     *
     * @return array{ok: bool, released?: int, code?: string, message?: string, http_status?: int}
     */
    public function expireReservation(int $orderId): array
    {
        return $this->releaseReservation(
            orderId: $orderId,
            targetStatus: self::RESERVATION_STATUS_CANCELLED,
            reason: 'reservation_ttl_expired',
        );
    }

    /**
     * Revisa y cancela todas las reservas cuyo `expires_at` ya haya vencido.
     *
     * Es ejecutado por un worker programado (cron/scheduler) para liberar automáticamente
     * el stock de pedidos abandonados. Procesa en lotes para no saturar la base de datos.
     *
     * @param int $batchSize Cantidad máxima de órdenes a procesar por ejecución.
     *
     * @return array{ok: bool, scanned_orders: int, cancelled_orders: int, errors: int}
     */
    public function reconcileExpiredReservations(int $batchSize = 200): array
    {
        // Si la tabla no existe, no hay nada que reconciliar.
        if (!$this->hasReservationTable()) {
            return [
                'ok' => true,
                'scanned_orders' => 0,
                'cancelled_orders' => 0,
                'errors' => 0,
            ];
        }

        // Garantiza un lote mínimo de 1 orden.
        $limit = max(1, $batchSize);
        // Busca órdenes con reservas vencidas (expires_at <= now() y status=reserved).
        $orderIds = DB::table('stock_reservations')
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->distinct()
            ->limit($limit)
            ->pluck('order_id')
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->values();

        $cancelled = 0;
        $errors = 0;

        foreach ($orderIds as $orderId) {
            $result = $this->expireReservation((int) $orderId);
            // Solo cuenta como cancelada si efectivamente se liberaron filas (released > 0).
            if (($result['ok'] ?? false) && (int) ($result['released'] ?? 0) > 0) {
                $cancelled++;
                continue;
            }

            // Si la operación falló, lo contamos como error para monitoreo.
            if (!($result['ok'] ?? false)) {
                $errors++;
            }
        }

        return [
            'ok' => true,
            'scanned_orders' => $orderIds->count(),
            'cancelled_orders' => $cancelled,
            'errors' => $errors,
        ];
    }

    /**
     * Reconoce y corrige los contadores 'reserved' en Redis contra la base de datos.
     *
     * Útil como tarea de reparación cuando los contadores en Redis se desincronizan
     * (por ejemplo, tras un fallo de red o reinicio del servidor Redis).
     * Compara `SUM(quantity)` de la BD con el valor actual en Redis y lo corrige si difiere.
     *
     * @param int $batchSize Cantidad de claves a procesar por ejecución.
     *
     * @return array{ok: bool, updated_keys: int}
     */
    public function reconcileReservationCounters(int $batchSize = 500): array
    {
        if (!$this->hasReservationTable()) {
            return [
                'ok' => true,
                'updated_keys' => 0,
            ];
        }

        $limit = max(1, $batchSize);
        // Obtiene la suma de cantidades reservadas agrupadas por reservation_key.
        $rows = DB::table('stock_reservations')
            ->select('reservation_key', DB::raw('SUM(quantity) as reserved_quantity'))
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->groupBy('reservation_key')
            ->limit($limit)
            ->get();

        $updated = 0;

        foreach ($rows as $row) {
            $reservationKey = (string) ($row->reservation_key ?? '');
            // Salta filas sin reservation_key (datos corruptos).
            if ($reservationKey === '') {
                continue;
            }

            $reservedQuantity = max(0, (int) ($row->reserved_quantity ?? 0));
            $redisReservedKey = $this->reservedRedisKey($reservationKey);
            $currentReserved = (int) (Redis::get($redisReservedKey) ?? 0);

            // Si ya están sincronizados, no hace falta escribir.
            if ($currentReserved === $reservedQuantity) {
                continue;
            }

            // Si la BD reporta 0, elimina la clave en Redis; de lo contrario, la actualiza.
            if ($reservedQuantity <= 0) {
                Redis::del($redisReservedKey);
            } else {
                Redis::set($redisReservedKey, (string) $reservedQuantity);
            }

            $updated++;
        }

        return [
            'ok' => true,
            'updated_keys' => $updated,
        ];
    }

    /**
     * Normaliza y agrupa los ítems de una reserva.
     *
     * - Convierte valores a enteros y descarta aquellos con datos inválidos (ID <= 0 o quantity <= 0).
     * - Agrupa por `size_variant_id` para sumar cantidades de un mismo producto repetido.
     *
     * @param array $items Lista de ítems sin normalizar.
     *
     * @return Collection<int, array{product_id: int, size_variant_id: int, quantity: int}>
     */
    private function normalizeReservationItems(array $items): Collection
    {
        $grouped = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $sizeVariantId = (int) ($item['size_variant_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            // Descarta ítems con IDs o cantidades no positivas.
            if ($productId <= 0 || $sizeVariantId <= 0 || $quantity <= 0) {
                continue;
            }

            $reservationKey = (string) $sizeVariantId;
            // Inicializa el grupo si es la primera vez que vemos este size_variant_id.
            if (!array_key_exists($reservationKey, $grouped)) {
                $grouped[$reservationKey] = [
                    'product_id' => $productId,
                    'size_variant_id' => $sizeVariantId,
                    'quantity' => 0,
                ];
            }

            // Acumula la cantidad para este size_variant_id.
            $grouped[$reservationKey]['quantity'] += $quantity;
        }

        return collect(array_values($grouped));
    }

    /**
     * Normaliza ítems para el flujo de confirmación sin reserva previa (fallback).
     *
     * Reutiliza la misma lógica de `normalizeReservationItems` para mantener consistencia.
     * Ver `confirmWithoutReservation` para contexto de uso.
     *
     * @param array $items Lista de ítems sin normalizar.
     *
     * @return Collection<int, array{product_id: int, size_variant_id: int, quantity: int}>
     */
    private function normalizeFallbackItems(array $items): Collection
    {
        return $this->normalizeReservationItems($items);
    }

    /**
     * Inicializa las claves de stock en Redis para una variante si aún no existen.
     *
     * Consulta el stock actual al catalog-service vía HTTP y lo almacena en Redis.
     * Usa un lock distribuido para evitar que múltiples workers inicialicen la misma clave
     * concurrentemente. También inicializa el contador 'reserved' en 0 si no existe.
     *
     * @param int $sizeVariantId ID de la variante (talle/tamaño) a inicializar.
     *
     * @throws \RuntimeException Si no se puede adquirir el lock de inicialización.
     */
    private function ensureStockKeyInitialized(int $sizeVariantId): void
    {
        $stockKey = $this->stockRedisKey((string) $sizeVariantId);
        // Si la clave ya existe en Redis, no hace falta inicializar.
        $currentStock = Redis::get($stockKey);
        if ($currentStock !== null) {
            return;
        }

        /** @var mixed $redisStore */
        $redisStore = Cache::store('redis');
        $initializationLock = $redisStore->lock(
            $this->reservationLockKey("init:{$sizeVariantId}"),
            max(2, $this->lockSeconds()),
        );

        // Si otro worker ya está inicializando esta variante, lanzamos excepción.
        if (!$initializationLock instanceof Lock || !$initializationLock->get()) {
            throw new \RuntimeException('No se pudo inicializar el stock en Redis porque el lock está ocupado.');
        }

        try {
            // Doble verificación: puede que otro worker ya haya inicializado mientras esperábamos el lock.
            $currentStock = Redis::get($stockKey);
            if ($currentStock !== null) {
                return;
            }

            // Obtiene el stock actual desde el catalog-service.
            $remoteStock = $this->fetchVariantStockFromCatalog($sizeVariantId);
            // Guarda el stock en Redis y asegura que el contador 'reserved' exista (inicia en 0).
            Redis::set($stockKey, (string) max(0, $remoteStock));
            Redis::setnx($this->reservedRedisKey((string) $sizeVariantId), '0');
        } finally {
            $initializationLock->release();
        }
    }

    /**
     * Obtiene el stock actual de una variante desde el catalog-service.
     *
     * Realiza una petición HTTP GET al endpoint interno del catalog-service.
     * Los mensajes de error se muestran directamente al usuario en el frontend.
     *
     * @param int $sizeVariantId ID de la variante a consultar.
     *
     * @return int Cantidad de stock disponible (nunca negativo).
     *
     * @throws \InvalidArgumentException Si la variante no existe (404) o falla validación (422).
     * @throws \RuntimeException         Si el servicio no responde o la respuesta es inválida.
     */
    private function fetchVariantStockFromCatalog(int $sizeVariantId): int
    {
        $endpoint = $this->resolveCatalogVariantEndpoint();

        $response = Http::acceptJson()
            ->timeout(5)
            ->get("{$endpoint}/{$sizeVariantId}");

        // La variante no existe en el catálogo (fue eliminada o desactivada).
        if ($response->status() === 404) {
            throw new \InvalidArgumentException('Uno de los productos de tu carrito ya no está disponible. Actualiza el carrito para continuar.');
        }

        // Error de validación desde catalog-service (ej. ID malformado).
        if ($response->status() === 422) {
            $apiMessage = trim((string) ($response->json('message') ?? ''));
            throw new \InvalidArgumentException(
                $apiMessage !== ''
                    ? $apiMessage
                    : 'No fue posible validar uno de los productos seleccionados. Revisa tu carrito e intenta de nuevo.'
            );
        }

        // Cualquier otro error HTTP se considera indisponibilidad.
        if (!$response->successful()) {
            throw new \RuntimeException('No fue posible consultar stock de variante en catalog-service.');
        }

        $payload = $response->json('data');
        if (!is_array($payload)) {
            throw new \RuntimeException('Respuesta inválida del catalog-service para variante.');
        }

        // El campo puede llamarse 'quantity' o 'stock' según la versión del catalog-service.
        $stockCandidates = [
            $payload['quantity'] ?? null,
            $payload['stock'] ?? null,
        ];

        foreach ($stockCandidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            return max(0, (int) $candidate);
        }

        throw new \RuntimeException('La variante no expone cantidad de stock en catalog-service.');
    }

    /**
     * Reserva stock en Redis de forma atómica usando un script Lua.
     *
     * El script Lua recibe:
     * - KEYS[1]: clave del stock disponible (stock:{reservationKey})
     * - KEYS[2]: clave del stock reservado (reserved:{reservationKey})
     * - ARGV[1]: cantidad a reservar
     *
     * Retornos del script:
     * - {1, remaining}: éxito, stock descontado.
     * - {-1, available}: stock insuficiente, devuelve el disponible actual.
     * - {-2, stock}: clave de stock no existe o no está inicializada.
     *
     * @param string $reservationKey Identificador de la variante (size_variant_id como string).
     * @param int    $quantity       Cantidad a reservar.
     *
     * @return array{ok: bool, code?: string, message?: string}
     */
    private function reserveRedisStock(string $reservationKey, int $quantity): array
    {
        $lua = <<<'LUA'
local stock = tonumber(redis.call('GET', KEYS[1]) or '-1')
if stock < 0 then
    return {-2, stock}
end
if stock < tonumber(ARGV[1]) then
    return {-1, stock}
end
local remaining = redis.call('DECRBY', KEYS[1], ARGV[1])
redis.call('INCRBY', KEYS[2], ARGV[1])
return {1, remaining}
LUA;

        $result = Redis::eval(
            $lua,
            2,
            $this->stockRedisKey($reservationKey),
            $this->reservedRedisKey($reservationKey),
            $quantity,
        );

        $status = (int) ($result[0] ?? 0);
        // Éxito: se descontó el stock y se incrementó el contador reserved.
        if ($status === 1) {
            return ['ok' => true];
        }

        // Stock insuficiente: devolvemos el disponible para que el frontend muestre el mensaje.
        if ($status === -1) {
            $available = (int) ($result[1] ?? 0);
            return [
                'ok' => false,
                'code' => 'out_of_stock',
                'message' => "Stock insuficiente. Disponible en este momento: {$available}.",
            ];
        }

        // Clave de stock no inicializada (nunca debería ocurrir si se llamó a ensureStockKeyInitialized).
        return [
            'ok' => false,
            'code' => 'stock_key_missing',
            'message' => 'No se pudo inicializar stock en Redis para una variante.',
        ];
    }

    /**
     * Libera stock reservado en Redis de forma atómica usando un script Lua.
     *
     * Incrementa el stock disponible (KEYS[1]) y decrementa o elimina el contador reservado (KEYS[2]).
     * Si la cantidad a liberar es mayor o igual al contador reservado, elimina la clave reserved por completo.
     *
     * @param string $reservationKey Identificador de la variante.
     * @param int    $quantity       Cantidad a liberar.
     *
     * @return array{ok: bool, code?: string, message?: string, http_status?: int}
     */
    private function releaseRedisStock(string $reservationKey, int $quantity): array
    {
        $lua = <<<'LUA'
redis.call('INCRBY', KEYS[1], ARGV[1])
local reserved = tonumber(redis.call('GET', KEYS[2]) or '0')
if reserved <= tonumber(ARGV[1]) then
    redis.call('DEL', KEYS[2])
else
    redis.call('DECRBY', KEYS[2], ARGV[1])
end
return {1}
LUA;

        $result = Redis::eval(
            $lua,
            2,
            $this->stockRedisKey($reservationKey),
            $this->reservedRedisKey($reservationKey),
            $quantity,
        );

        if ((int) ($result[0] ?? 0) === 1) {
            return ['ok' => true];
        }

        return $this->failure(
            code: 'release_failed',
            message: 'No fue posible liberar stock reservado en Redis.',
            httpStatus: self::UNAVAILABLE_HTTP_STATUS,
        );
    }

    /**
     * Confirma la liberación del contador 'reserved' en Redis después de un commit exitoso.
     *
     * Similar a `releaseRedisStock` pero solo opera sobre la clave 'reserved' (no toca el stock).
     * Se ejecuta después de que catalog-service ya descontó el stock permanentemente.
     *
     * @param string $reservationKey Identificador de la variante.
     * @param int    $quantity       Cantidad confirmada a descontar del contador reserved.
     */
    private function confirmRedisReservation(string $reservationKey, int $quantity): void
    {
        $lua = <<<'LUA'
local reserved = tonumber(redis.call('GET', KEYS[1]) or '0')
if reserved <= tonumber(ARGV[1]) then
    redis.call('DEL', KEYS[1])
else
    redis.call('DECRBY', KEYS[1], ARGV[1])
end
return 1
LUA;

        try {
            Redis::eval(
                $lua,
                1,
                $this->reservedRedisKey($reservationKey),
                $quantity,
            );
        } catch (Throwable $exception) {
            // No relanzamos la excepción: la confirmación ya ocurrió en catalog-service y BD.
            Log::warning('No se pudo ajustar contador reserved al confirmar reserva.', [
                'reservation_key' => $reservationKey,
                'quantity' => $quantity,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Revierte todas las reservas parciales en Redis cuando falla una operación de reserva.
     *
     * Itera sobre el mapa `reservedByKey` (variante => cantidad) y llama a `releaseRedisStock`
     * para cada una. Los errores individuales se registran pero no interrumpen la reversión.
     *
     * @param array<string, int> $reservedByKey Mapa de reservation_key => cantidad reservada.
     */
    private function rollbackRedisReservation(array $reservedByKey): void
    {
        if (empty($reservedByKey)) {
            return;
        }

        foreach ($reservedByKey as $reservationKey => $quantity) {
            try {
                $this->releaseRedisStock((string) $reservationKey, (int) $quantity);
            } catch (Throwable $exception) {
                Log::warning('No se pudo revertir reserva parcial de Redis.', [
                    'reservation_key' => $reservationKey,
                    'quantity' => $quantity,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Almacena el payload de la reserva en Redis con un TTL.
     *
     * Este payload permite a otros servicios (como el worker de expiración) conocer
     * rápidamente el estado de la reserva sin consultar la base de datos.
     * Se usa `JSON_UNESCAPED_UNICODE` para preservar caracteres UTF-8 en los mensajes.
     *
     * @param int             $orderId     ID de la orden.
     * @param string          $orderNumber Número único de orden.
     * @param Collection      $items       Ítems normalizados de la reserva.
     * @param int             $ttlSeconds  Tiempo de vida en segundos.
     * @param CarbonInterface $expiresAt   Fecha y hora de expiración calculada.
     *
     * @throws \RuntimeException Si no se puede serializar el payload a JSON.
     */
    private function storeReservationInRedis(
        int $orderId,
        string $orderNumber,
        Collection $items,
        int $ttlSeconds,
        CarbonInterface $expiresAt,
    ): void {
        $payload = [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'status' => self::RESERVATION_STATUS_RESERVED,
            'expires_at' => $expiresAt->toIso8601String(),
            'items' => $items->map(static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'size_variant_id' => (int) $item['size_variant_id'],
                'quantity' => (int) $item['quantity'],
            ])->values()->all(),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            throw new \RuntimeException('No se pudo serializar la reserva en Redis.');
        }

        Redis::setex(
            $this->reservationRedisKey($orderId),
            $ttlSeconds,
            $json,
        );
    }

    /**
     * Persiste las filas de reserva en la tabla `stock_reservations` de MySQL.
     *
     * Cada ítem se convierte en una fila con estado 'reserved' y la fecha de expiración.
     * Esta es la capa durable que permite recuperar reservas ante un reinicio de Redis.
     *
     * @param int             $orderId   ID de la orden.
     * @param Collection      $items     Ítems normalizados de la reserva.
     * @param CarbonInterface $expiresAt Fecha y hora de expiración.
     */
    private function persistReservationRows(int $orderId, Collection $items, CarbonInterface $expiresAt): void
    {
        $rows = $items->map(static function (array $item) use ($orderId, $expiresAt): array {
            return [
                'order_id' => $orderId,
                'product_id' => (int) $item['product_id'],
                'size_variant_id' => (int) $item['size_variant_id'],
                'reservation_key' => (string) $item['size_variant_id'],
                'quantity' => (int) $item['quantity'],
                'status' => self::RESERVATION_STATUS_RESERVED,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values()->all();

        DB::table('stock_reservations')->insert($rows);
    }

    /**
     * Refresca el payload de reserva en Redis con nuevos valores (usado en extensión de TTL).
     *
     * Lee el payload existente de Redis (si lo hay), actualiza los campos `expires_at`,
     * `order_id`, `status` e `items`, y lo guarda con el nuevo TTL.
     *
     * @param int             $orderId   ID de la orden.
     * @param Collection      $rows      Filas actuales de la reserva desde la BD.
     * @param int             $ttlSeconds Nuevo TTL en segundos.
     * @param CarbonInterface $expiresAt Nueva fecha de expiración.
     */
    private function refreshReservationRedisPayload(int $orderId, Collection $rows, int $ttlSeconds, CarbonInterface $expiresAt): void
    {
        $redisKey = $this->reservationRedisKey($orderId);
        $currentPayload = Redis::get($redisKey);
        $decoded = is_string($currentPayload) ? json_decode($currentPayload, true) : null;
        $payload = is_array($decoded) ? $decoded : [];

        $payload['order_id'] = $orderId;
        $payload['status'] = self::RESERVATION_STATUS_RESERVED;
        $payload['expires_at'] = $expiresAt->toIso8601String();
        $payload['items'] = $rows->map(static fn ($row): array => [
            'product_id' => (int) ($row->product_id ?? 0),
            'size_variant_id' => (int) ($row->size_variant_id ?? 0),
            'quantity' => (int) ($row->quantity ?? 0),
        ])->values()->all();

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            return;
        }

        Redis::setex($redisKey, $ttlSeconds, $json);
    }

    /**
     * Confirma inventario directamente desde los ítems de la orden sin una reserva previa en Redis.
     *
     * Es un flujo de contingencia (fallback) para cuando la reserva expiró pero el usuario
     * igualmente completa el pago. Lee los ítems desde la tabla `order_items` y llama
     * al catalog-service con `strictReservation=false` para que descuente stock sin
     * exigir que exista una reserva activa.
     *
     * @param int $orderId ID de la orden.
     *
     * @return array{ok: bool, fallback_commit?: bool, items?: array, code?: string, message?: string, http_status?: int}
     */
    private function confirmWithoutReservation(int $orderId): array
    {
        // Obtiene los ítems originales de la orden desde la base de datos.
        $orderItems = DB::table('order_items')
            ->where('order_id', $orderId)
            ->get(['product_id', 'size_variant_id', 'quantity']);

        // Si la orden no tiene ítems, no se puede confirmar nada.
        if ($orderItems->isEmpty()) {
            return $this->failure(
                code: 'order_items_missing',
                message: 'No hay ítems en la orden para confirmar inventario.',
                httpStatus: self::VALIDATION_HTTP_STATUS,
            );
        }

        // Normaliza los ítems usando el mismo método que la reserva (agrupa por size_variant_id).
        $normalizedItems = $this->normalizeFallbackItems(
            $orderItems->map(static fn ($item): array => [
                'product_id' => (int) ($item->product_id ?? 0),
                'size_variant_id' => (int) ($item->size_variant_id ?? 0),
                'quantity' => (int) ($item->quantity ?? 0),
            ])->values()->all(),
        );

        if ($normalizedItems->isEmpty()) {
            return $this->failure(
                code: 'fallback_items_invalid',
                message: 'No fue posible confirmar inventario: faltan variantes en los ítems de la orden.',
                httpStatus: self::VALIDATION_HTTP_STATUS,
            );
        }

        // Envía el commit al catalog-service sin exigir reserva previa.
        $commitResult = $this->commitInventoryInCatalog(
            orderId: $orderId,
            items: $normalizedItems->values()->all(),
            strictReservation: false,
        );

        if (!($commitResult['ok'] ?? false)) {
            return $commitResult;
        }

        // Inserta filas directamente con status 'confirmed' y metadata del fallback.
        DB::table('stock_reservations')->insert(
            $normalizedItems->map(static function (array $item) use ($orderId): array {
                return [
                    'order_id' => $orderId,
                    'product_id' => (int) $item['product_id'],
                    'size_variant_id' => (int) $item['size_variant_id'],
                    'reservation_key' => (string) $item['size_variant_id'],
                    'quantity' => (int) $item['quantity'],
                    'status' => self::RESERVATION_STATUS_CONFIRMED,
                    'confirmed_at' => now(),
                    'metadata' => json_encode(['fallback_commit' => true], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->values()->all(),
        );

        $this->realtimePublisher->publish('stock.reservation.confirmed', [
            'order_id' => $orderId,
            'fallback_commit' => true,
            'items' => $normalizedItems->values()->all(),
        ]);

        return [
            'ok' => true,
            'fallback_commit' => true,
            'items' => $normalizedItems->values()->all(),
        ];
    }

    /**
     * Envía una solicitud HTTP POST al catalog-service para confirmar (descontar) inventario.
     *
     * El catalog-service es el sistema de registro oficial del stock. Esta llamada descuenta
     * el stock de forma permanente. Si `strictReservation=true`, el catalog-service exige
     * que exista una reserva previa; si es false, descuenta directamente (fallback).
     *
     * Maneja los códigos HTTP de respuesta:
     * - 2xx: éxito.
     * - 409: stock insuficiente (OUT_OF_STOCK_HTTP_STATUS).
     * - 422: error de validación (VALIDATION_HTTP_STATUS).
     * - Otros: error de servicio (UNAVAILABLE_HTTP_STATUS).
     *
     * @param int     $orderId          ID de la orden.
     * @param array   $items            Lista de ítems a confirmar.
     * @param bool    $strictReservation Si true, exige reserva previa en catalog-service.
     *
     * @return array{ok: bool, data?: mixed, code?: string, message?: string, http_status?: int}
     */
    private function commitInventoryInCatalog(int $orderId, array $items, bool $strictReservation): array
    {
        $endpoint = $this->resolveCatalogCommitEndpoint();

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->post($endpoint, [
                    'order_id' => $orderId,
                    'items' => $items,
                    'strict_reservation' => $strictReservation,
                ]);

            // Respuesta exitosa (2xx): el catalog-service confirmó el descuento de stock.
            if ($response->successful()) {
                return [
                    'ok' => true,
                    'data' => $response->json('data'),
                ];
            }

            $status = $response->status();
            $message = (string) ($response->json('message') ?? 'No fue posible confirmar inventario en catalog-service.');

            // 409: conflicto de negocio (stock insuficiente).
            if ($status === 409) {
                return $this->failure(
                    code: 'insufficient_stock',
                    message: $message,
                    httpStatus: self::OUT_OF_STOCK_HTTP_STATUS,
                );
            }

            // 422: error de validación (parámetros inválidos).
            if ($status === 422) {
                return $this->failure(
                    code: 'catalog_validation_failed',
                    message: $message,
                    httpStatus: self::VALIDATION_HTTP_STATUS,
                );
            }

            // Cualquier otro código HTTP se considera indisponibilidad del servicio.
            return $this->failure(
                code: 'catalog_commit_failed',
                message: $message,
                httpStatus: self::UNAVAILABLE_HTTP_STATUS,
            );
        } catch (Throwable $exception) {
            Log::error('No se pudo confirmar inventario en catalog-service.', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);

            return $this->failure(
                code: 'catalog_unavailable',
                message: 'No fue posible confirmar inventario en catalog-service.',
                httpStatus: self::UNAVAILABLE_HTTP_STATUS,
            );
        }
    }

    /**
     * Resuelve la URL completa del endpoint de variantes en catalog-service.
     *
     * Lee la configuración de `config('services.catalog')` y construye la URL
     * normalizada (base + path). Si la base termina en '/api', no duplica el segmento.
     * Usada por `fetchVariantStockFromCatalog`.
     *
     * @return string URL completa del endpoint (ej: "http://catalog-service:8000/api/internal/variants").
     */
    private function resolveCatalogVariantEndpoint(): string
    {
        $baseUrl = rtrim((string) config('services.catalog.base_url', 'http://catalog-service:8000/api'), '/');
        $path = trim((string) config('services.catalog.variant_path', '/internal/variants'));
        $path = '/' . ltrim($path, '/');

        // Si la base ya incluye '/api', concatenamos directamente.
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . $path;
        }

        return $baseUrl . '/api' . $path;
    }

    /**
     * Resuelve la URL completa del endpoint de commit de inventario en catalog-service.
     *
     * Similar a `resolveCatalogVariantEndpoint` pero para el endpoint de confirmación
     * de inventario. Usada por `commitInventoryInCatalog`.
     *
     * @return string URL completa del endpoint (ej: "http://catalog-service:8000/api/internal/inventory/commit").
     */
    private function resolveCatalogCommitEndpoint(): string
    {
        $baseUrl = rtrim((string) config('services.catalog.base_url', 'http://catalog-service:8000/api'), '/');
        $path = trim((string) config('services.catalog.inventory_commit_path', '/internal/inventory/commit'));
        $path = '/' . ltrim($path, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . $path;
        }

        return $baseUrl . '/api' . $path;
    }

    /**
     * Genera la clave de Redis para el stock disponible de una variante.
     *
     * Formato: `stock:{reservationKey}`
     *
     * @param string $reservationKey Identificador único de la variante.
     *
     * @return string Clave de Redis para el stock disponible.
     */
    private function stockRedisKey(string $reservationKey): string
    {
        return "stock:{$reservationKey}";
    }

    /**
     * Genera la clave de Redis para el contador de stock reservado de una variante.
     *
     * Formato: `reserved:{reservationKey}`
     *
     * @param string $reservationKey Identificador único de la variante.
     *
     * @return string Clave de Redis para el stock reservado.
     */
    private function reservedRedisKey(string $reservationKey): string
    {
        return "reserved:{$reservationKey}";
    }

    /**
     * Genera la clave de Redis para el payload de una reserva de orden.
     *
     * Formato: `reservation:{orderId}`
     *
     * @param int $orderId ID de la orden.
     *
     * @return string Clave de Redis para el payload de la reserva.
     */
    private function reservationRedisKey(int $orderId): string
    {
        return "reservation:{$orderId}";
    }

    /**
     * Genera la clave de Redis para el lock distribuido de una variante.
     *
     * Formato: `lock:stock:{reservationKey}`
     * Los locks evitan que dos procesos modifiquen el mismo stock concurrentemente.
     *
     * @param string $reservationKey Identificador único de la variante.
     *
     * @return string Clave de Redis para el lock distribuido.
     */
    private function reservationLockKey(string $reservationKey): string
    {
        return "lock:stock:{$reservationKey}";
    }

    /**
     * Libera todos los locks distribuidos adquiridos durante una operación.
     *
     * @param array<string, Lock> $locks Mapa de reservation_key => instancia Lock de Redis.
     */
    private function releaseLocks(array $locks): void
    {
        foreach ($locks as $lock) {
            try {
                $lock->release();
            } catch (Throwable) {
                // Ignora errores al liberar locks para no interrumpir el flujo principal.
            }
        }
    }

    /**
     * Verifica si la tabla `stock_reservations` existe en la base de datos.
     *
     * El resultado se cachea en `$this->reservationTableExists` para evitar
     * múltiples consultas a `Schema::hasTable` durante una misma petición.
     * Si la consulta falla (por ejemplo, la conexión a BD no está disponible),
     * retorna false como valor seguro.
     *
     * @return bool True si la tabla existe, false en caso contrario.
     */
    private function hasReservationTable(): bool
    {
        if ($this->reservationTableExists !== null) {
            return $this->reservationTableExists;
        }

        try {
            $this->reservationTableExists = Schema::hasTable('stock_reservations');
        } catch (Throwable) {
            $this->reservationTableExists = false;
        }

        return $this->reservationTableExists;
    }

    /**
     * Retorna la cantidad de segundos que debe durar un lock distribuido.
     *
     * Se configura via `config('services.stock_reservations.lock_seconds')`
     * con un valor mínimo de 2 segundos y un default de 10 segundos.
     *
     * @return int Segundos de duración del lock.
     */
    private function lockSeconds(): int
    {
        return max(2, (int) config('services.stock_reservations.lock_seconds', 10));
    }

    /**
     * Construye un array de error estandarizado para respuestas fallidas.
     *
     * Todos los métodos públicos del servicio retornan arrays con esta estructura
     * cuando ocurre un error, permitiendo al llamante manejar la respuesta de forma uniforme.
     *
     * @param string $code      Código interno de error (ej: 'out_of_stock', 'stock_busy').
     * @param string $message   Mensaje descriptivo para el frontend (en español).
     * @param int    $httpStatus Código HTTP recomendado para la respuesta.
     *
     * @return array{ok: false, code: string, message: string, http_status: int}
     */
    private function failure(string $code, string $message, int $httpStatus): array
    {
        return [
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'http_status' => $httpStatus,
        ];
    }
}
