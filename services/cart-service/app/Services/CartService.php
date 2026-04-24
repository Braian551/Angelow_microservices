<?php

namespace App\Services;

use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Cart Service
 *
 * Business logic for shopping cart operations.
 * Cart data stays in cart-service, product data is requested from catalog-service.
 */
class CartService
{
    private const PRODUCT_FALLBACK_IMAGE = '/images/default-product.jpg';

    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
    ) {}

    /**
     * Add a product variant to the cart.
     *
     * @throws \InvalidArgumentException
     */
    public function addToCart(?string $userId, ?string $sessionId, int $productId, ?int $colorVariantId, int $sizeVariantId, int $quantity = 1): array
    {
        $variant = $this->fetchVariantData($sizeVariantId);
        if (!$variant) {
            throw new \InvalidArgumentException('Variante de tamano no encontrada');
        }

        if ((int) ($variant['product_id'] ?? 0) !== $productId) {
            throw new \InvalidArgumentException('La variante de tamano no pertenece a este producto');
        }

        if ($colorVariantId !== null && (int) ($variant['color_variant_id'] ?? 0) !== $colorVariantId) {
            throw new \InvalidArgumentException('La variante de color no coincide con la variante de tamano seleccionada');
        }

        $availableStock = $this->resolveRealtimeAvailableStock(
            $sizeVariantId,
            (int) ($variant['quantity'] ?? 0),
        );

        if ($availableStock < $quantity) {
            throw new \InvalidArgumentException($this->buildOutOfStockMessage($availableStock));
        }

        $cartId = $this->cartRepository->getOrCreateCart($userId, $sessionId);
        $existing = $this->cartRepository->findExistingItem($cartId, $productId, $colorVariantId, $sizeVariantId);

        if ($existing) {
            $newQuantity = $existing->quantity + $quantity;
            if ($availableStock < $newQuantity) {
                throw new \InvalidArgumentException($this->buildOutOfStockMessage($availableStock));
            }
            $this->cartRepository->updateItemQuantity($existing->id, $newQuantity);
        } else {
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
     * Get all items from the user's cart with details from catalog-service.
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

            if (!array_key_exists($productId, $productsById)) {
                $productsById[$productId] = $this->fetchProductData($productId);
            }

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
     * Update the quantity of a cart item.
     *
     * @throws \InvalidArgumentException
     */
    public function updateQuantity(int $itemId, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser al menos 1');
        }

        $item = $this->cartRepository->findItem($itemId);
        if (!$item) {
            throw new \InvalidArgumentException('Articulo no encontrado en el carrito');
        }

        $variant = $this->fetchVariantData((int) $item->size_variant_id);
        $availableStock = $this->resolveRealtimeAvailableStock(
            (int) $item->size_variant_id,
            (int) ($variant['quantity'] ?? 0),
        );

        $currentQuantity = (int) ($item->quantity ?? 0);
        if ($quantity > $currentQuantity && $availableStock < $quantity) {
            throw new \InvalidArgumentException($this->buildOutOfStockMessage($availableStock));
        }

        $this->cartRepository->updateItemQuantity($itemId, $quantity);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeFromCart(int $itemId): void
    {
        $this->cartRepository->removeItem($itemId);
    }

    /**
     * Get product IDs in the user's cart (lightweight).
     */
    public function getCartProductIds(?string $userId, ?string $sessionId): array
    {
        return $this->cartRepository->getCartProductIds($userId, $sessionId);
    }

    /**
     * Dispara recordatorios para carritos con inactividad prolongada.
     */
    public function dispatchAbandonedCartReminders(int $inactiveMinutes = 180, int $limit = 120): array
    {
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
            if ($userId === '') {
                continue;
            }

            $summary['processed']++;

            $rateLimitKey = "cart:abandoned:reminder:user:{$userId}";
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

                if ($token !== '') {
                    $request = $request->withHeaders(['X-Internal-Token' => $token]);
                }

                $response = $request->post($endpoint, $payload);
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

    private function fetchProductData(int $productId): ?array
    {
        $response = Http::timeout(6)->get($this->catalogBaseUrl() . "/internal/products/{$productId}");
        if (!$response->successful()) {
            return null;
        }

        $payload = $response->json();
        return is_array($payload['data'] ?? null) ? $payload['data'] : null;
    }

    private function fetchVariantData(int $sizeVariantId): ?array
    {
        $response = Http::timeout(6)->get($this->catalogBaseUrl() . "/internal/variants/{$sizeVariantId}");
        if (!$response->successful()) {
            return null;
        }

        $payload = $response->json();
        return is_array($payload['data'] ?? null) ? $payload['data'] : null;
    }

    private function catalogBaseUrl(): string
    {
        return rtrim((string) env('CATALOG_API_URL', 'http://localhost:8002/api'), '/');
    }

    private function resolveNotificationEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.notifications.base_url', 'http://notification-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/notifications';
        }

        return $baseUrl . '/api/notifications';
    }

    private function buildAbandonedCartMessage(int $itemsCount): string
    {
        $storeUrl = trim((string) config('services.frontend.store_url', 'http://localhost:5173'));
        if ($storeUrl === '') {
            $storeUrl = 'http://localhost:5173';
        }

        $cartUrl = rtrim($storeUrl, '/') . '/carrito';

        $suffix = $itemsCount === 1 ? 'producto' : 'productos';

        return "Aún tienes {$itemsCount} {$suffix} en tu carrito. Retoma tu compra en {$cartUrl}.";
    }

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

    private function buildOutOfStockMessage(int $availableStock): string
    {
        return "Stock insuficiente. Disponible en este momento: {$availableStock}.";
    }
}
