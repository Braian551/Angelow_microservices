<?php

namespace App\Repositories\Contracts;

/**
 * Interfaz del repositorio de carrito.
 *
 * Define el contrato para el acceso a datos de carritos e ítems.
 * Separa la lógica de persistencia de la lógica de negocio (CartService).
 *
 * @see QueryBuilderCartRepository Implementación con Query Builder
 * @see CartService Consumidor de esta interfaz
 */
interface CartRepositoryInterface
{
    /**
     * Obtiene un carrito existente o crea uno nuevo para el usuario/sesión.
     * Si el usuario ya tiene un carrito, retorna el más reciente.
     */
    public function getOrCreateCart(?string $userId, ?string $sessionId): int;

    /**
     * Retorna todos los ítems de un carrito con sus datos básicos.
     */
    public function getItems(int $cartId): array;

    /**
     * Agrega un nuevo ítem al carrito.
     */
    public function addItem(int $cartId, int $productId, ?int $colorVariantId, int $sizeVariantId, int $quantity): void;

    /**
     * Actualiza la cantidad de un ítem existente.
     */
    public function updateItemQuantity(int $itemId, int $quantity): void;

    /**
     * Elimina un ítem del carrito por su ID.
     */
    public function removeItem(int $itemId): void;

    /**
     * Busca un ítem del carrito por su ID.
     */
    public function findItem(int $itemId): ?object;

    /**
     * Busca un ítem existente que coincida con producto + variantes.
     * Usado para evitar duplicados cuando se agrega el mismo producto.
     */
    public function findExistingItem(int $cartId, int $productId, ?int $colorVariantId, int $sizeVariantId): ?object;

    /**
     * Obtiene solo los IDs de productos en el carrito del usuario.
     * Método ligero para el frontend (marcar productos en carrito).
     */
    public function getCartProductIds(?string $userId, ?string $sessionId): array;
}
