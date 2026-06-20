<?php

namespace App\Repositories\Contracts;

// Comentario de mantenimiento: Este contrato fija las operaciones que debe cumplir cualquier repositorio del dominio.

/**
 * Contract for Category repository.
 */
interface CategoryRepositoryInterface
{
    /**
     * Get all active categories.
     */
    public function getAllActive(): array;

    /**
     * Find a category by ID.
     */
    public function findById(int $id): ?object;

    /**
     * Get all active collections.
     */
    public function getAllCollections(): array;
}
