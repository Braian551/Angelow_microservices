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

class StockReservationService
{
    private const RESERVATION_STATUS_RESERVED = 'reserved';
    private const RESERVATION_STATUS_CONFIRMED = 'confirmed';
    private const RESERVATION_STATUS_EXPIRED = 'expired';
    private const RESERVATION_STATUS_CANCELLED = 'cancelled';

    private const OUT_OF_STOCK_HTTP_STATUS = 409;
    private const CONFLICT_HTTP_STATUS = 429;
    private const VALIDATION_HTTP_STATUS = 422;
    private const UNAVAILABLE_HTTP_STATUS = 503;

    private ?bool $reservationTableExists = null;

    public function __construct(
        private readonly StockReservationRealtimePublisher $realtimePublisher,
    ) {}

    public function reserveForOrder(int $orderId, string $orderNumber, array $items, int $ttlSeconds): array
    {
        if (!$this->hasReservationTable()) {
            return $this->failure(
                code: 'reservation_table_missing',
                message: 'No existe la tabla stock_reservations. Ejecuta la migración antes de crear pedidos.',
                httpStatus: 500,
            );
        }

        $normalizedItems = $this->normalizeReservationItems($items);
        if ($normalizedItems->isEmpty()) {
            return $this->failure(
                code: 'invalid_items',
                message: 'La orden no tiene ítems válidos para reserva de inventario.',
                httpStatus: self::VALIDATION_HTTP_STATUS,
            );
        }

        $ttl = max(60, $ttlSeconds);
        $locks = [];
        $reservedByKey = [];

        try {
            foreach ($normalizedItems as $item) {
                $this->ensureStockKeyInitialized((int) $item['size_variant_id']);
            }

            $reservationKeys = $normalizedItems
                ->pluck('size_variant_id')
                ->map(static fn ($value): string => (string) $value)
                ->unique()
                ->sort()
                ->values()
                ->all();

            foreach ($reservationKeys as $reservationKey) {
                /** @var mixed $redisStore */
                $redisStore = Cache::store('redis');
                $lock = $redisStore->lock(
                    $this->reservationLockKey($reservationKey),
                    $this->lockSeconds(),
                );

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

            foreach ($normalizedItems as $item) {
                $reservationKey = (string) $item['size_variant_id'];
                $quantity = (int) $item['quantity'];

                $reserveResult = $this->reserveRedisStock($reservationKey, $quantity);
                if (!($reserveResult['ok'] ?? false)) {
                    $this->rollbackRedisReservation($reservedByKey);
                    $this->releaseLocks($locks);

                    return $this->failure(
                        code: $reserveResult['code'] ?? 'out_of_stock',
                        message: $reserveResult['message'] ?? 'No hay stock disponible para uno de los productos del pedido.',
                        httpStatus: self::OUT_OF_STOCK_HTTP_STATUS,
                    );
                }

                $reservedByKey[$reservationKey] = ($reservedByKey[$reservationKey] ?? 0) + $quantity;
            }

            $expiresAt = now()->addSeconds($ttl);

            $this->storeReservationInRedis($orderId, $orderNumber, $normalizedItems, $ttl, $expiresAt);
            $this->persistReservationRows($orderId, $normalizedItems, $expiresAt);

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
            $this->rollbackRedisReservation($reservedByKey);

            Log::error('No se pudo reservar inventario en Redis.', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'error' => $exception->getMessage(),
            ]);

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
            $this->releaseLocks($locks);
        }
    }

    public function extendReservation(int $orderId, int $ttlSeconds): bool
    {
        if (!$this->hasReservationTable()) {
            return false;
        }

        $ttl = max(60, $ttlSeconds);

        $rows = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->get();

        if ($rows->isEmpty()) {
            return false;
        }

        $candidateExpiry = now()->addSeconds($ttl);
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

        $expiresAt = $candidateExpiry;
        if ($maxCurrentExpiryTimestamp > $candidateExpiry->timestamp) {
            $expiresAt = Carbon::createFromTimestamp($maxCurrentExpiryTimestamp);
        }

        $redisTtl = max(60, now()->diffInSeconds($expiresAt, false));

        DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->update([
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

        $this->refreshReservationRedisPayload($orderId, $rows, $redisTtl, $expiresAt);

        $this->realtimePublisher->publish('stock.reservation.extended', [
            'order_id' => $orderId,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return true;
    }

    public function confirmReservation(int $orderId): array
    {
        if (!$this->hasReservationTable()) {
            return $this->failure(
                code: 'reservation_table_missing',
                message: 'No existe la tabla stock_reservations.',
                httpStatus: 500,
            );
        }

        $rows = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->get();

        if ($rows->isEmpty()) {
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

            return $this->confirmWithoutReservation($orderId);
        }

        $items = $rows->map(static fn ($row): array => [
            'product_id' => (int) ($row->product_id ?? 0),
            'size_variant_id' => (int) ($row->size_variant_id ?? 0),
            'quantity' => (int) ($row->quantity ?? 0),
        ])->values()->all();

        $commitResult = $this->commitInventoryInCatalog($orderId, $items, true);
        if (!($commitResult['ok'] ?? false)) {
            return $commitResult;
        }

        DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->update([
                'status' => self::RESERVATION_STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]);

        $rowsByKey = $rows
            ->groupBy(static fn ($row): string => (string) $row->reservation_key)
            ->map(static fn (Collection $itemsForKey): int => $itemsForKey->sum(static fn ($row): int => (int) $row->quantity));

        foreach ($rowsByKey as $reservationKey => $quantity) {
            $this->confirmRedisReservation((string) $reservationKey, (int) $quantity);
        }

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

    public function releaseReservation(int $orderId, string $targetStatus = self::RESERVATION_STATUS_CANCELLED, ?string $reason = null): array
    {
        if (!$this->hasReservationTable()) {
            return $this->failure(
                code: 'reservation_table_missing',
                message: 'No existe la tabla stock_reservations.',
                httpStatus: 500,
            );
        }

        $status = in_array($targetStatus, [self::RESERVATION_STATUS_EXPIRED, self::RESERVATION_STATUS_CANCELLED], true)
            ? $targetStatus
            : self::RESERVATION_STATUS_CANCELLED;

        $rows = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'ok' => true,
                'released' => 0,
            ];
        }

        $locks = [];
        $releaseByKey = $rows
            ->groupBy(static fn ($row): string => (string) $row->reservation_key)
            ->map(static fn (Collection $itemsForKey): int => $itemsForKey->sum(static fn ($row): int => (int) $row->quantity))
            ->sortKeys();

        try {
            foreach ($releaseByKey->keys()->all() as $reservationKey) {
                /** @var mixed $redisStore */
                $redisStore = Cache::store('redis');
                $lock = $redisStore->lock(
                    $this->reservationLockKey((string) $reservationKey),
                    $this->lockSeconds(),
                );

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

            foreach ($releaseByKey as $reservationKey => $quantity) {
                $releaseResult = $this->releaseRedisStock((string) $reservationKey, (int) $quantity);
                if (!($releaseResult['ok'] ?? false)) {
                    return $releaseResult;
                }
            }

            DB::table('stock_reservations')
                ->where('order_id', $orderId)
                ->where('status', self::RESERVATION_STATUS_RESERVED)
                ->update([
                    'status' => $status,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

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

    public function expireReservation(int $orderId): array
    {
        return $this->releaseReservation(
            orderId: $orderId,
            targetStatus: self::RESERVATION_STATUS_EXPIRED,
            reason: 'ttl_expired',
        );
    }

    public function reconcileExpiredReservations(int $batchSize = 200): array
    {
        if (!$this->hasReservationTable()) {
            return [
                'ok' => true,
                'scanned_orders' => 0,
                'expired_orders' => 0,
                'errors' => 0,
            ];
        }

        $limit = max(1, $batchSize);
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

        $expired = 0;
        $errors = 0;

        foreach ($orderIds as $orderId) {
            $result = $this->expireReservation((int) $orderId);
            if (($result['ok'] ?? false) && (int) ($result['released'] ?? 0) > 0) {
                $expired++;
                continue;
            }

            if (!($result['ok'] ?? false)) {
                $errors++;
            }
        }

        return [
            'ok' => true,
            'scanned_orders' => $orderIds->count(),
            'expired_orders' => $expired,
            'errors' => $errors,
        ];
    }

    public function reconcileReservationCounters(int $batchSize = 500): array
    {
        if (!$this->hasReservationTable()) {
            return [
                'ok' => true,
                'updated_keys' => 0,
            ];
        }

        $limit = max(1, $batchSize);
        $rows = DB::table('stock_reservations')
            ->select('reservation_key', DB::raw('SUM(quantity) as reserved_quantity'))
            ->where('status', self::RESERVATION_STATUS_RESERVED)
            ->groupBy('reservation_key')
            ->limit($limit)
            ->get();

        $updated = 0;

        foreach ($rows as $row) {
            $reservationKey = (string) ($row->reservation_key ?? '');
            if ($reservationKey === '') {
                continue;
            }

            $reservedQuantity = max(0, (int) ($row->reserved_quantity ?? 0));
            $redisReservedKey = $this->reservedRedisKey($reservationKey);
            $currentReserved = (int) (Redis::get($redisReservedKey) ?? 0);

            if ($currentReserved === $reservedQuantity) {
                continue;
            }

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

    private function normalizeReservationItems(array $items): Collection
    {
        $grouped = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $sizeVariantId = (int) ($item['size_variant_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $sizeVariantId <= 0 || $quantity <= 0) {
                continue;
            }

            $reservationKey = (string) $sizeVariantId;
            if (!array_key_exists($reservationKey, $grouped)) {
                $grouped[$reservationKey] = [
                    'product_id' => $productId,
                    'size_variant_id' => $sizeVariantId,
                    'quantity' => 0,
                ];
            }

            $grouped[$reservationKey]['quantity'] += $quantity;
        }

        return collect(array_values($grouped));
    }

    private function normalizeFallbackItems(array $items): Collection
    {
        return $this->normalizeReservationItems($items);
    }

    private function ensureStockKeyInitialized(int $sizeVariantId): void
    {
        $stockKey = $this->stockRedisKey((string) $sizeVariantId);
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

        if (!$initializationLock instanceof Lock || !$initializationLock->get()) {
            throw new \RuntimeException('No se pudo inicializar el stock en Redis porque el lock está ocupado.');
        }

        try {
            $currentStock = Redis::get($stockKey);
            if ($currentStock !== null) {
                return;
            }

            $remoteStock = $this->fetchVariantStockFromCatalog($sizeVariantId);
            Redis::set($stockKey, (string) max(0, $remoteStock));
            Redis::setnx($this->reservedRedisKey((string) $sizeVariantId), '0');
        } finally {
            $initializationLock->release();
        }
    }

    private function fetchVariantStockFromCatalog(int $sizeVariantId): int
    {
        $endpoint = $this->resolveCatalogVariantEndpoint();

        $response = Http::acceptJson()
            ->timeout(5)
            ->get("{$endpoint}/{$sizeVariantId}");

        if ($response->status() === 404) {
            throw new \InvalidArgumentException('Uno de los productos de tu carrito ya no está disponible. Actualiza el carrito para continuar.');
        }

        if ($response->status() === 422) {
            $apiMessage = trim((string) ($response->json('message') ?? ''));
            throw new \InvalidArgumentException(
                $apiMessage !== ''
                    ? $apiMessage
                    : 'No fue posible validar uno de los productos seleccionados. Revisa tu carrito e intenta de nuevo.'
            );
        }

        if (!$response->successful()) {
            throw new \RuntimeException('No fue posible consultar stock de variante en catalog-service.');
        }

        $payload = $response->json('data');
        if (!is_array($payload)) {
            throw new \RuntimeException('Respuesta inválida del catalog-service para variante.');
        }

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
        if ($status === 1) {
            return ['ok' => true];
        }

        if ($status === -1) {
            $available = (int) ($result[1] ?? 0);
            return [
                'ok' => false,
                'code' => 'out_of_stock',
                'message' => "Stock insuficiente. Disponible en este momento: {$available}.",
            ];
        }

        return [
            'ok' => false,
            'code' => 'stock_key_missing',
            'message' => 'No se pudo inicializar stock en Redis para una variante.',
        ];
    }

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
            Log::warning('No se pudo ajustar contador reserved al confirmar reserva.', [
                'reservation_key' => $reservationKey,
                'quantity' => $quantity,
                'error' => $exception->getMessage(),
            ]);
        }
    }

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

    private function confirmWithoutReservation(int $orderId): array
    {
        $orderItems = DB::table('order_items')
            ->where('order_id', $orderId)
            ->get(['product_id', 'size_variant_id', 'quantity']);

        if ($orderItems->isEmpty()) {
            return $this->failure(
                code: 'order_items_missing',
                message: 'No hay ítems en la orden para confirmar inventario.',
                httpStatus: self::VALIDATION_HTTP_STATUS,
            );
        }

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

        $commitResult = $this->commitInventoryInCatalog(
            orderId: $orderId,
            items: $normalizedItems->values()->all(),
            strictReservation: false,
        );

        if (!($commitResult['ok'] ?? false)) {
            return $commitResult;
        }

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

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'data' => $response->json('data'),
                ];
            }

            $status = $response->status();
            $message = (string) ($response->json('message') ?? 'No fue posible confirmar inventario en catalog-service.');

            if ($status === 409) {
                return $this->failure(
                    code: 'insufficient_stock',
                    message: $message,
                    httpStatus: self::OUT_OF_STOCK_HTTP_STATUS,
                );
            }

            if ($status === 422) {
                return $this->failure(
                    code: 'catalog_validation_failed',
                    message: $message,
                    httpStatus: self::VALIDATION_HTTP_STATUS,
                );
            }

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

    private function resolveCatalogVariantEndpoint(): string
    {
        $baseUrl = rtrim((string) config('services.catalog.base_url', 'http://catalog-service:8000/api'), '/');
        $path = trim((string) config('services.catalog.variant_path', '/internal/variants'));
        $path = '/' . ltrim($path, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . $path;
        }

        return $baseUrl . '/api' . $path;
    }

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

    private function stockRedisKey(string $reservationKey): string
    {
        return "stock:{$reservationKey}";
    }

    private function reservedRedisKey(string $reservationKey): string
    {
        return "reserved:{$reservationKey}";
    }

    private function reservationRedisKey(int $orderId): string
    {
        return "reservation:{$orderId}";
    }

    private function reservationLockKey(string $reservationKey): string
    {
        return "lock:stock:{$reservationKey}";
    }

    /**
     * @param array<string, Lock> $locks
     */
    private function releaseLocks(array $locks): void
    {
        foreach ($locks as $lock) {
            try {
                $lock->release();
            } catch (Throwable) {
                // Evita romper flujo por liberación de lock.
            }
        }
    }

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

    private function lockSeconds(): int
    {
        return max(2, (int) config('services.stock_reservations.lock_seconds', 10));
    }

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
