<?php

namespace App\Repositories;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Query Builder implementation of the Category repository.
 *
 * Migrated from angelow/tienda/productos.php category and collection queries.
 */
class QueryBuilderCategoryRepository implements CategoryRepositoryInterface
{
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

    public function findById(int $id): ?object
    {
        return DB::table('categories')
            ->where('id', $id)
            ->first();
    }

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
