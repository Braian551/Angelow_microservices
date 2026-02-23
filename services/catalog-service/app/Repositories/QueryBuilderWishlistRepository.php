<?php

namespace App\Repositories;

use App\Repositories\Contracts\WishlistRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Query Builder implementation of the Wishlist repository.
 *
 * Migrated from angelow/tienda/api/wishlist/ endpoints.
 */
class QueryBuilderWishlistRepository implements WishlistRepositoryInterface
{
    public function add(string $userId, int $productId): bool
    {
        // Verify that the product exists and is active
        $productExists = DB::table('products')
            ->where('id', $productId)
            ->where('is_active', 1)
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

    public function remove(string $userId, int $productId): bool
    {
        $affected = DB::table('wishlist')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();

        return $affected > 0;
    }

    public function getByUser(string $userId): array
    {
        return DB::table('wishlist as w')
            ->join('products as p', 'w.product_id', '=', 'p.id')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                     ->where('pi.is_primary', '=', 1);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('w.user_id', $userId)
            ->where('p.is_active', 1)
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

    public function exists(string $userId, int $productId): bool
    {
        return DB::table('wishlist')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }
}
