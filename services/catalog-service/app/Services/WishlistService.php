<?php

namespace App\Services;

use App\Repositories\Contracts\WishlistRepositoryInterface;

/**
 * Wishlist Service
 *
 * Contains business logic for managing user's wishlist/favorites.
 */
class WishlistService
{
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
    ) {}

    /**
     * Toggle a product in the user's wishlist.
     * If already present, remove it. Otherwise, add it.
     */
    public function toggle(string $userId, int $productId): array
    {
        if ($this->wishlistRepository->exists($userId, $productId)) {
            $this->wishlistRepository->remove($userId, $productId);
            return ['action' => 'removed', 'message' => 'Producto eliminado de tu lista de deseos'];
        }

        $added = $this->wishlistRepository->add($userId, $productId);

        if (!$added) {
            throw new \App\Exceptions\NotFoundException('Producto no disponible');
        }

        return ['action' => 'added', 'message' => 'Producto añadido a tu lista de deseos'];
    }

    /**
     * Get all wishlist items for a user.
     */
    public function getUserWishlist(string $userId): array
    {
        return $this->wishlistRepository->getByUser($userId);
    }
}
