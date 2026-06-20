<?php

namespace App\Repositories;

// Comentario de mantenimiento: Este repositorio encapsula consultas a datos para aislar a los servicios del detalle SQL.

use App\Repositories\Contracts\WishlistRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Query Builder implementation of the Wishlist repository.
 *
 * Migrated from angelow/tienda/api/wishlist/ endpoints.
 */
class QueryBuilderWishlistRepository implements WishlistRepositoryInterface
{
    /**
     * Inserta la relación solicitada cuando las validaciones de existencia lo permiten.
     */
    public function add(string $userId, int $productId): bool
    {
        // Verify that the product exists and is active
        $productExists = DB::table('products')
            ->where('id', $productId)
            ->where('is_active', true)
            ->exists();

        if (!$productExists) {
            return false;
        }

        // Check if already in wishlist
        if ($this->exists($userId, $productId)) {
            return true; // Already exists — idempotent
        }

        DB::table('wishlist')->insert([
            'user_id'    => $userId,
            'product_id' => $productId,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * Elimina la relación solicitada sin afectar otros registros del usuario.
     */

    public function remove(string $userId, int $productId): bool
    {
        $affected = DB::table('wishlist')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();

        return $affected > 0;
    }

    /**
     * Consulta registros asociados a un usuario y los transforma al contrato de salida.
     */

    public function getByUser(string $userId): array
    {
        return DB::table('wishlist as w')
            ->join('products as p', 'w.product_id', '=', 'p.id')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                     ->where('pi.is_primary', '=', true);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('w.user_id', $userId)
            ->where('p.is_active', true)
            ->select([
                'p.id', 'p.name', 'p.slug', 'p.price', 'p.compare_price',
                'pi.image_path as primary_image',
                'c.name as category_name',
                'w.created_at as added_at',
            ])
            ->orderByDesc('w.created_at')
            ->get()
            ->map(fn($i) => (array) $i)
            ->toArray();
    }

    /**
     * Verifica existencia de una relación para evitar duplicados y decisiones ambiguas.
     */

    public function exists(string $userId, int $productId): bool
    {
        return DB::table('wishlist')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }
}
