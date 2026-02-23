<?php

namespace App\Repositories\Contracts;

/**
 * Contract for Wishlist repository.
 */
interface WishlistRepositoryInterface
{
    /**
     * Add a product to the user's wishlist.
     */
    public function add(string $userId, int $productId): bool;

    /**
     * Remove a product from the user's wishlist.
     */
    public function remove(string $userId, int $productId): bool;

    /**
     * Get all wishlist items for a user.
     */
    public function getByUser(string $userId): array;

    /**
     * Check if a product is already in the user's wishlist.
     */
    public function exists(string $userId, int $productId): bool;
}
