<?php

namespace App\Services;

use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Servicio de carrito de compras (cart-service)
 *
 * Contiene toda la lógica de negocio del carrito: agregar productos,
 * actualizar cantidades, eliminar ítems, calcular subtotales y
 * gestionar recordatorios de carritos abandonados.
 *
 * Los datos del carrito se persisten localmente (tablas carts + cart_items),
 * mientras que la información de productos y variantes se obtiene bajo
 * demanda desde el catalog-service vía HTTP interno.
 *
 * @see CartRepositoryInterface
 * @see QueryBuilderCartRepository
 */
class CartService
{
    private const PRODUCT_FALLBACK_IMAGE = '/images/default-product.jpg';

    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    /**
     * Agrega una variante de producto al carrito.
     *
     * Valida que la variante exista en catalog-service, que pertenezca
     * al producto indicado, que el color sea coherente con la talla,
     * y que haya stock disponible (consultando Redis en tiempo real).
     * Si el producto ya está en el carrito, incrementa la cantidad.
     *
     * @throws \InvalidArgumentException cuando la variante no existe,
     *         no corresponde al producto, o el stock es insuficiente.
     */
    public function addToCart(?string $userId, ?string $sessionId, int $productId, ?int $colorVariantId, int $sizeVariantId, int $quantity = 1): array
    {
        $variant = $this->fetchVariantData($sizeVariantId);
        // Sin variante válida no se puede calcular precio, stock ni atributos de compra.
        if (!$variant) {
            throw new \InvalidArgumentException('Variante de tamano no encontrada');
        }

        // Evita que el frontend agregue una talla asociada a otro producto.
        if ((int) ($variant['product_id'] ?? 0) !== $productId) {
            throw new \InvalidArgumentException('La variante de tamano no pertenece a este producto');
        }

        // Cuando el color viene explícito, debe coincidir con la talla seleccionada en catálogo.
        if ($colorVariantId !== null && (int) ($variant['color_variant_id'] ?? 0) !== $colorVariantId) {
            throw new \InvalidArgumentException('La variante de color no coincide con la variante de tamano seleccionada');
        }

        $availableStock = $this->resolveRealtimeAvailableStock(
            $sizeVariantId,
            (int) ($variant['quantity'] ?? 0),
        );

        // La primera validación corta solicitudes que ya exceden el inventario disponible.
        if ($availableStock < $quantity) {
            throw new \InvalidArgumentException($this->buildOutOfStockMessage($availableStock));
        }

        $cartId = $this->cartRepository->getOrCreateCart($userId, $sessionId);
        $existing = $this->cartRepository->findExistingItem($cartId, $productId, $colorVariantId, $sizeVariantId);

        if ($existing) {
            $newQuantity = $existing->quantity + $quantity;
            // Al sumar sobre un ítem existente se valida el total acumulado, no solo la cantidad nueva.
            if ($availableStock < $newQuantity) {
                throw new \InvalidArgumentException($this->buildOutOfStockMessage($availableStock));
            }
            $this->cartRepository->updateItemQuantity($existing->id, $newQuantity);
        } else {
            // Si no hay coincidencia exacta de producto/talla/color, se crea una línea nueva de carrito.
            $this->cartRepository->addItem($cartId, $productId, $colorVariantId, $sizeVariantId, $quantity);
        }

        return [
            'cart_id' => $cartId,
            'message' => 'Producto anadido al carrito',
            'variant_info' => [
                'size' => $variant['size_name'] ?? null,
                'color' => $variant['color_name'] ?? 'N/A',
                'price' => (float) ($variant['price'] ?? 0),
            ],
        ];
    }

    /**
     * Obtiene todos los ítems del carrito con datos enriquecidos
     * desde el catalog-service (nombre, imagen, precio, slug).
     *
     * Agrupa las consultas HTTP por producto y variante para evitar
     * llamadas duplicadas. Calcula subtotal y conteo total de items.
     */
    public function getCartItems(?string $userId, ?string $sessionId): array
    {
        $cartId = $this->cartRepository->getOrCreateCart($userId, $sessionId);
        $rawItems = $this->cartRepository->getItems($cartId);

        $productsById = [];
        $variantsById = [];
        $itemCount = 0;

        foreach ($rawItems as $rawItem) {
            $productId = (int) $rawItem['product_id'];
            $sizeVariantId = (int) $rawItem['size_variant_id'];

            // Cache local por producto para no consultar catalog-service varias veces en el mismo carrito.
            if (!array_key_exists($productId, $productsById)) {
                $productsById[$productId] = $this->fetchProductData($productId);
            }

            // Cache local por variante para reutilizar precio, color, talla e inventario base.
            if (!array_key_exists($sizeVariantId, $variantsById)) {
                $variantsById[$sizeVariantId] = $this->fetchVariantData($sizeVariantId);
            }
        }

        $items = [];
        $subtotal = 0;

        foreach ($rawItems as $rawItem) {
            $product = $productsById[(int) $rawItem['product_id']] ?? null;
            $variant = $variantsById[(int) $rawItem['size_variant_id']] ?? null;
            $quantity = (int) $rawItem['quantity'];
            $productImage = $variant['variant_image'] ?? $product['primary_image'] ?? self::PRODUCT_FALLBACK_IMAGE;
            $sizeVariantId = isset($rawItem['size_variant_id']) ? (int) $rawItem['size_variant_id'] : 0;
            $colorVariantId = isset($rawItem['color_variant_id']) ? (int) $rawItem['color_variant_id'] : 0;

            // Se arma un contrato estable para el frontend aunque catalog-service no responda algún dato.
            $item = [
                'item_id' => (int) $rawItem['item_id'],
                'quantity' => $quantity,
                'product_id' => (int) $rawItem['product_id'],
                'color_variant_id' => $colorVariantId > 0 ? $colorVariantId : null,
                'size_variant_id' => $sizeVariantId > 0 ? $sizeVariantId : null,
                'product_name' => $product['name'] ?? 'Producto',
                'product_slug' => $product['slug'] ?? '',
                'product_image' => $productImage,
                'price' => (float) ($variant['price'] ?? 0),
                'compare_price' => isset($variant['compare_price']) ? (float) $variant['compare_price'] : null,
                'available_stock' => $this->resolveRealtimeAvailableStock(
                    $sizeVariantId,
                    (int) ($variant['quantity'] ?? 0),
                ),
                'size_name' => $variant['size_name'] ?? null,
                'color_name' => $variant['color_name'] ?? null,
                'color_hex' => $variant['color_hex'] ?? null,
            ];

            $item['line_total'] = $item['price'] * $item['quantity'];
            $subtotal += $item['line_total'];
            $itemCount += $quantity;
            $items[] = $item;
        }

        return [
            'cart_id' => $cartId,
            'items' => $items,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
        ];
    }

    /**
     * Actualiza la cantidad de un ítem existente en el carrito.
     *
     * Valida que el ítem exista y que la nueva cantidad no supere
     * el stock disponible (consultando catalog-service + Redis).
     *
     * @throws \InvalidArgumentException si el ítem no existe o el stock es insuficiente.
     */
    public function updateQuantity(int $itemId, int $quantity): void
    {
        // La cantidad mínima se valida de nuevo aquí para proteger usos fuera del controlador HTTP.
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1');
        }

        $item = $this->cartRepository->findItem($itemId);
        // No se actualizan líneas inexistentes para evitar inconsistencias silenciosas.
        if (!$item) {
            throw new \InvalidArgumentException('Articulo no encontrado en el carrito');
        }

        $variant = $this->fetchVariantData((int) $item->size_variant_id);
        $availableStock = $this->resolveRealtimeAvailableStock(
            (int) $item->size_variant_id,
            (int) ($variant['quantity'] ?? 0),
        );

        $currentQuantity = (int) ($item->quantity ?? 0);
        // Si la operación aumenta unidades, se confirma stock; reducir cantidad siempre es seguro.
        if ($quantity > $currentQuantity && $availableStock < $quantity) {
            throw new \InvalidArgumentException($this->buildOutOfStockMessage($availableStock));
        }

        $this->cartRepository->updateItemQuantity($itemId, $quantity);
    }

    /**
     * Elimina un ítem del carrito por su ID.
     * No lanza excepción si el ítem no existe (eliminación idempotente).
     */
    public function removeFromCart(int $itemId): void
    {
        $this->cartRepository->removeItem($itemId);
    }

    /**
     * Obtiene solo los IDs de productos en el carrito del usuario.
     * Método ligero usado por el frontend para marcar productos
     * que ya están en el carrito (ícono de carrito lleno).
     */
    public function getCartProductIds(?string $userId, ?string $sessionId): array
    {
        return $this->cartRepository->getCartProductIds($userId, $sessionId);
    }

    /**
     * Dispara recordatorios para carritos abandonados (inactividad prolongada).
     *
     * Consulta carritos con user_id no nulo cuya última actualización
     * supere el umbral de inactividad. Para cada candidato, envía una
     * notificación push y un correo electrónico a través del
     * notification-service, respetando un rate-limit por usuario en Redis
     * (6 horas entre recordatorios) para evitar spam.
     *
     * @param int $inactiveMinutes Minutos de inactividad para considerar abandonado (30-10080).
     * @param int $limit Máximo de carritos a procesar por ejecución (1-500).
     * @return array Resumen con totales de procesados, enviados, fallidos y rate-limited.
     */
    public function dispatchAbandonedCartReminders(int $inactiveMinutes = 180, int $limit = 120): array
    {
        // Se acotan los parámetros para proteger el job frente a llamadas manuales o payloads extremos.
        $inactiveMinutes = max(30, min($inactiveMinutes, 10080));
        $limit = max(1, min($limit, 500));

        $summary = [
            'total_candidates' => 0,
            'processed' => 0,
            'notifications' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
            'emails' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
            'rate_limited' => 0,
        ];

        $endpoint = $this->resolveNotificationEndpoint();
        // Sin endpoint configurado no se envían recordatorios, pero se retorna un resumen válido.
        if ($endpoint === null) {
            return $summary;
        }

        $threshold = now()->subMinutes($inactiveMinutes);

        $rows = DB::table('carts as c')
            ->join('cart_items as ci', 'ci.cart_id', '=', 'c.id')
            ->select('c.id', 'c.user_id', 'c.updated_at', DB::raw('SUM(ci.quantity) as items_count'))
            ->whereNotNull('c.user_id')
            ->where('c.updated_at', '<=', $threshold)
            ->groupBy('c.id', 'c.user_id', 'c.updated_at')
            ->orderBy('c.updated_at')
            ->limit($limit)
            ->get();

        $summary['total_candidates'] = $rows->count();

        foreach ($rows as $row) {
            $userId = trim((string) ($row->user_id ?? ''));
            // Solo se envían recordatorios cuando el carrito puede asociarse a un usuario real.
            if ($userId === '') {
                continue;
            }

            $summary['processed']++;

            $rateLimitKey = "cart:abandoned:reminder:user:{$userId}";
            // El rate-limit en Redis evita repetir recordatorios al mismo usuario dentro de la ventana definida.
            if (Redis::get($rateLimitKey) !== null) {
                $summary['rate_limited']++;
                continue;
            }

            $itemsCount = max(1, (int) ($row->items_count ?? 1));
            $payload = [
                'user_id' => $userId,
                'event_key' => 'cart_reminder',
                'title' => 'Tu carrito te está esperando',
                'message' => $this->buildAbandonedCartMessage($itemsCount),
                'related_entity_type' => 'cart',
                'related_entity_id' => (int) ($row->id ?? 0),
                'send_push' => true,
                'send_email' => true,
            ];

            try {
                $request = Http::acceptJson()->timeout(8);
                $token = trim((string) config('services.notifications.internal_token', ''));

                // Si existe token interno, se envía para que notification-service valide la llamada.
                if ($token !== '') {
                    $request = $request->withHeaders(['X-Internal-Token' => $token]);
                }

                $response = $request->post($endpoint, $payload);
                // Una respuesta HTTP fallida cuenta como fallo de ambos canales solicitados.
                if (!$response->successful()) {
                    $summary['notifications']['failed']++;
                    $summary['emails']['failed']++;
                    continue;
                }

                $body = $response->json();
                $notificationSent = (bool) ($body['notification_sent'] ?? false);
                $emailSent = (bool) ($body['email_sent'] ?? false);
                $skipped = (bool) ($body['skipped'] ?? false);
                $reason = (string) ($body['reason'] ?? '');

                // Se separa el conteo push/email porque notification-service puede resolverlos de forma distinta.
                if ($notificationSent) {
                    $summary['notifications']['sent']++;
                } elseif ($skipped) {
                    $summary['notifications']['skipped']++;
                } else {
                    $summary['notifications']['failed']++;
                }

                if ($emailSent) {
                    $summary['emails']['sent']++;
                } elseif ($skipped || $reason === 'email_not_resolved') {
                    $summary['emails']['skipped']++;
                } else {
                    $summary['emails']['failed']++;
                }

                // Evita envíos repetitivos durante una ventana razonable.
                Redis::setex($rateLimitKey, 21600, '1');
            } catch (Throwable $exception) {
                $summary['notifications']['failed']++;
                $summary['emails']['failed']++;

                Log::warning('No se pudo disparar recordatorio de carrito abandonado.', [
                    'user_id' => $userId,
                    'cart_id' => (int) ($row->id ?? 0),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    /**
     * Obtiene los datos de un producto desde catalog-service.
     * Consulta el endpoint interno /api/internal/products/{id}.
     * Si falla o no hay datos, retorna null para mostrar valores por defecto.
     */
    private function fetchProductData(int $productId): ?array
    {
        $response = Http::timeout(6)->get($this->catalogBaseUrl() . "/internal/products/{$productId}");
        // Si catalog-service no responde OK, se usan datos por defecto en el armado del carrito.
        if (!$response->successful()) {
            return null;
        }

        $payload = $response->json();
        // El contrato esperado ubica el producto dentro de data.
        return is_array($payload['data'] ?? null) ? $payload['data'] : null;
    }

    /**
     * Obtiene los datos de una variante (talla) desde catalog-service.
     * Incluye precio, nombre de talla, color, stock e imagen de variante.
     */
    private function fetchVariantData(int $sizeVariantId): ?array
    {
        $response = Http::timeout(6)->get($this->catalogBaseUrl() . "/internal/variants/{$sizeVariantId}");
        // La variante es obligatoria para validar stock y atributos de compra.
        if (!$response->successful()) {
            return null;
        }

        $payload = $response->json();
        // El contrato esperado ubica la variante dentro de data.
        return is_array($payload['data'] ?? null) ? $payload['data'] : null;
    }

    /**
     * Retorna la URL base del catalog-service desde la variable de entorno.
     * Se usa para todas las llamadas HTTP internas a productos y variantes.
     */
    private function catalogBaseUrl(): string
    {
        return rtrim((string) env('CATALOG_API_URL', 'http://localhost:8002/api'), '/');
    }

    /**
     * Resuelve la URL del endpoint de notificaciones en notification-service.
     * Maneja ambos formatos de URL (con o sin /api) para flexibilidad.
     * Retorna null si no hay URL configurada, desactivando los recordatorios.
     */
    private function resolveNotificationEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.notifications.base_url', 'http://notification-service:8000/api'));
        // Cadena vacía desactiva el envío sin romper el flujo del job.
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        // Soporta configuraciones que ya incluyen /api para no duplicar el segmento.
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/notifications';
        }

        return $baseUrl . '/api/notifications';
    }

    /**
     * Construye el mensaje de recordatorio para carrito abandonado.
     * Incluye la cantidad de productos y un enlace al carrito.
     * Usa singular "producto" o plural "productos" según el conteo.
     */
    private function buildAbandonedCartMessage(int $itemsCount): string
    {
        $storeUrl = trim((string) config('services.frontend.store_url', 'http://localhost:5173'));
        // Fallback local para que el mensaje siempre tenga un enlace usable en desarrollo.
        if ($storeUrl === '') {
            $storeUrl = 'http://localhost:5173';
        }

        $cartUrl = rtrim($storeUrl, '/') . '/carrito';

        $suffix = $itemsCount === 1 ? 'producto' : 'productos';

        return "Aún tienes {$itemsCount} {$suffix} en tu carrito. Retoma tu compra en {$cartUrl}.";
    }

    /**
     * Obtiene el stock disponible en tiempo real desde Redis.
     *
     * Primero consulta la clave "stock:{variantId}" que contiene el stock
     * absoluto sincronizado desde catalog-service. Si no existe, consulta
     * "reserved:{variantId}" para restar reservas activas al stock base.
     * Si Redis no está disponible, retorna el stock de catálogo como fallback.
     *
     * Este mecanismo evita sobreventas durante el checkout concurrente.
     */
    private function resolveRealtimeAvailableStock(int $sizeVariantId, int $fallbackQuantity): int
    {
        $safeFallback = max(0, $fallbackQuantity);

        try {
            // Redis guarda el stock disponible en tiempo real para checkout.
            $stockValue = Redis::get("stock:{$sizeVariantId}");
            if ($stockValue !== null && is_numeric((string) $stockValue)) {
                return max(0, (int) $stockValue);
            }

            $reservedValue = Redis::get("reserved:{$sizeVariantId}");
            if ($reservedValue !== null && is_numeric((string) $reservedValue)) {
                $reserved = max(0, (int) $reservedValue);
                return max(0, $safeFallback - $reserved);
            }
        } catch (Throwable) {
            // Si Redis falla, se mantiene el inventario calculado por catalog-service.
        }

        return $safeFallback;
    }

    /**
     * Construye un mensaje de error cuando el stock es insuficiente.
     * Muestra la cantidad disponible en ese momento.
     */
    private function buildOutOfStockMessage(int $availableStock): string
    {
        return "Stock insuficiente. Disponible en este momento: {$availableStock}.";
    }
}
