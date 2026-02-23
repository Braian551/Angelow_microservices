<?php

namespace App\Repositories\Contracts;

/**
 * Contract for Cart repository.
 *
 * Defines the data access interface for shopping cart operations.
 */
interface CartRepositoryInterface
{
    /**
     * Get or create a cart for a user or session.
     */
    public function getOrCreateCart(?string $userId, ?string $sessionId): int;

    /**
     * Get all items in a cart with product details.
     */
    public function getItems(int $cartId): array;

    /**
     * Add an item to the cart.
     */
    public function addItem(int $cartId, int $productId, ?int $colorVariantId, int $sizeVariantId, int $quantity): void;

    /**
     * Update the quantity of a cart item.
     */
    public function updateItemQuantity(int $itemId, int $quantity): void;

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): void;

    /**
     * Get a cart item by its ID.
     */
    public function findItem(int $itemId): ?object;

    /**
     * Find an existing cart item matching product/variant combination.
     */
    public function findExistingItem(int $cartId, int $productId, ?int $colorVariantId, int $sizeVariantId): ?object;

    /**
     * Get product IDs currently in the user's cart.
     */
    public function getCartProductIds(?string $userId, ?string $sessionId): array;
}
