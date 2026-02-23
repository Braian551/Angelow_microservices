<?php

namespace App\Services;

use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Cart Service
 *
 * Contains all business logic for shopping cart operations.
 * Delegates data access to the CartRepository.
 */
class CartService
{
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
        // Validate the size variant exists and belongs to the product
        $variant = DB::table('product_size_variants as psv')
            ->join('product_color_variants as pcv', 'psv.color_variant_id', '=', 'pcv.id')
            ->leftJoin('sizes as s', 'psv.size_id', '=', 's.id')
            ->leftJoin('colors as c', 'pcv.color_id', '=', 'c.id')
            ->where('psv.id', $sizeVariantId)
            ->select([
                'psv.*', 's.name as size_name',
                'pcv.product_id', 'pcv.id as color_variant_id',
                'c.name as color_name', 'pcv.color_id',
            ])
            ->first();

        if (!$variant) {
            throw new \InvalidArgumentException('Variante de tamaño no encontrada');
        }

        if ($variant->product_id != $productId) {
            throw new \InvalidArgumentException('La variante de tamaño no pertenece a este producto');
        }

        if ($colorVariantId && $variant->color_variant_id != $colorVariantId) {
            throw new \InvalidArgumentException('La variante de color no coincide con la variante de tamaño seleccionada');
        }

        if ($variant->quantity < $quantity) {
            throw new \InvalidArgumentException('No hay suficiente stock disponible');
        }

        $cartId = $this->cartRepository->getOrCreateCart($userId, $sessionId);

        // Check if item already exists
        $existing = $this->cartRepository->findExistingItem($cartId, $productId, $colorVariantId, $sizeVariantId);

        if ($existing) {
            $newQuantity = $existing->quantity + $quantity;
            if ($variant->quantity < $newQuantity) {
                throw new \InvalidArgumentException('No hay suficiente stock disponible para la cantidad solicitada');
            }
            $this->cartRepository->updateItemQuantity($existing->id, $newQuantity);
        } else {
            $this->cartRepository->addItem($cartId, $productId, $colorVariantId, $sizeVariantId, $quantity);
        }

        return [
            'cart_id'      => $cartId,
            'message'      => 'Producto añadido al carrito',
            'variant_info' => [
                'size'  => $variant->size_name,
                'color' => $variant->color_name ?? 'N/A',
                'price' => $variant->price,
            ],
        ];
    }

    /**
     * Get all items from the user's cart with details.
     */
    public function getCartItems(?string $userId, ?string $sessionId): array
    {
        $cartId = $this->cartRepository->getOrCreateCart($userId, $sessionId);
        $items  = $this->cartRepository->getItems($cartId);

        // Calculate totals
        $subtotal = 0;
        foreach ($items as &$item) {
            $item['line_total'] = $item['price'] * $item['quantity'];
            $subtotal += $item['line_total'];
        }

        return [
            'cart_id'    => $cartId,
            'items'      => $items,
            'item_count' => count($items),
            'subtotal'   => $subtotal,
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
            throw new \InvalidArgumentException('Artículo no encontrado en el carrito');
        }

        // Verify stock
        $stock = DB::table('product_size_variants')
            ->where('id', $item->size_variant_id)
            ->value('quantity');

        if ($stock < $quantity) {
            throw new \InvalidArgumentException('No hay suficiente stock disponible');
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
}
