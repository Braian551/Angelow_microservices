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

        return ['action' => 'added', 'message' => 'Producto anadido a tu lista de deseos'];
    }

    /**
     * Verifica si un producto ya existe en la lista de deseos del usuario.
     */
    public function hasProduct(string $userId, int $productId): bool
    {
        return $this->wishlistRepository->exists($userId, $productId);
    }

    /**
     * Agrega un producto de forma idempotente.
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function addProduct(string $userId, int $productId): void
    {
        if ($this->wishlistRepository->exists($userId, $productId)) {
            return;
        }

        $added = $this->wishlistRepository->add($userId, $productId);

        if (!$added) {
            throw new \App\Exceptions\NotFoundException('Producto no disponible');
        }
    }

    /**
     * Elimina un producto de la lista de deseos.
     */
    public function removeProduct(string $userId, int $productId): void
    {
        $this->wishlistRepository->remove($userId, $productId);
    }

    /**
     * Get all wishlist items for a user.
     */
    public function getUserWishlist(string $userId): array
    {
        return $this->wishlistRepository->getByUser($userId);
    }
}
