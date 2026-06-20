<?php

namespace App\Repositories;

// Comentario de mantenimiento: Este repositorio encapsula consultas a datos para aislar a los servicios del detalle SQL.

use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Query Builder implementation of the Category repository.
 *
 * Migrated from angelow/tienda/productos.php category and collection queries.
 */
class QueryBuilderCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Lista entidades activas necesarias para navegación o selectores.
     */
    public function getAllActive(): array
    {
        return DB::table('categories')
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->select(['id', 'name', 'slug', 'image'])
            ->get()
            ->map(fn($c) => (array) $c)
            ->toArray();
    }

    /**
     * Busca una entidad por identificador primario.
     */

    public function findById(int $id): ?object
    {
        return DB::table('categories')
            ->where('id', $id)
            ->first();
    }

    /**
     * Lista colecciones disponibles para filtros y navegación.
     */

    public function getAllCollections(): array
    {
        return DB::table('collections')
            ->where('is_active', true)
            ->orderByDesc('launch_date')
            ->select(['id', 'name', 'description', 'slug', 'image', 'launch_date'])
            ->get()
            ->map(fn($c) => (array) $c)
            ->toArray();
    }
}
