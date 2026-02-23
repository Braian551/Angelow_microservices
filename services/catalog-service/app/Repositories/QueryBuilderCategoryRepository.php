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
            ->where('is_active', 1)
            ->orderBy('name')
            ->select(['id', 'name', 'slug'])
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
            ->where('is_active', 1)
            ->orderBy('name')
            ->select(['id', 'name', 'description', 'slug'])
            ->get()
            ->map(fn($c) => (array) $c)
            ->toArray();
    }
}
