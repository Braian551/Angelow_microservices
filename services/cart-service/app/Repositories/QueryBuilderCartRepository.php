<?php

namespace App\Repositories;

use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Query Builder implementation of the Cart repository.
 *
 * Migrated from angelow/tienda/api/cart/ endpoints.
 * Uses DB facade instead of Eloquent ORM.
 */
class QueryBuilderCartRepository implements CartRepositoryInterface
{
    /**
     * Get or create a cart for a user or session.
     *
     * Migrated from add-cart.php cart lookup/creation logic.
     */
    public function getOrCreateCart(?string $userId, ?string $sessionId): int
    {
        $query = DB::table('carts');

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $cart = $query->orderByDesc('created_at')->first();

        if ($cart) {
            return $cart->id;
        }

        // Create new cart
        return DB::table('carts')->insertGetId([
            'user_id'    => $userId,
            'session_id' => $userId ? null : $sessionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get all items in a cart with product/variant details.
     */
    public function getItems(int $cartId): array
    {
        return DB::table('cart_items as ci')
            ->join('products as p', 'ci.product_id', '=', 'p.id')
            ->leftJoin('product_size_variants as psv', 'ci.size_variant_id', '=', 'psv.id')
            ->leftJoin('sizes as s', 'psv.size_id', '=', 's.id')
            ->leftJoin('product_color_variants as pcv', 'ci.color_variant_id', '=', 'pcv.id')
            ->leftJoin('colors as c', 'pcv.color_id', '=', 'c.id')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                     ->where('pi.is_primary', '=', 1);
            })
            ->where('ci.cart_id', $cartId)
            ->select([
                'ci.id as item_id',
                'ci.quantity',
                'p.id as product_id',
                'p.name as product_name',
                'p.slug as product_slug',
                'pi.image_path as product_image',
                'psv.price',
                'psv.compare_price',
                'psv.quantity as available_stock',
                's.name as size_name',
                'c.name as color_name',
                'c.hex_code as color_hex',
            ])
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(int $cartId, int $productId, ?int $colorVariantId, int $sizeVariantId, int $quantity): void
    {
        DB::table('cart_items')->insert([
            'cart_id'          => $cartId,
            'product_id'       => $productId,
            'color_variant_id' => $colorVariantId,
            'size_variant_id'  => $sizeVariantId,
            'quantity'         => $quantity,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItemQuantity(int $itemId, int $quantity): void
    {
        DB::table('cart_items')
            ->where('id', $itemId)
            ->update([
                'quantity'   => $quantity,
                'updated_at' => now(),
            ]);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): void
    {
        DB::table('cart_items')->where('id', $itemId)->delete();
    }

    /**
     * Get a cart item by its ID.
     */
    public function findItem(int $itemId): ?object
    {
        return DB::table('cart_items')->where('id', $itemId)->first();
    }

    /**
     * Find an existing cart item matching product/variant combination.
     */
    public function findExistingItem(int $cartId, int $productId, ?int $colorVariantId, int $sizeVariantId): ?object
    {
        $query = DB::table('cart_items')
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->where('size_variant_id', $sizeVariantId);

        if ($colorVariantId) {
            $query->where('color_variant_id', $colorVariantId);
        } else {
            $query->whereNull('color_variant_id');
        }

        return $query->first();
    }

    /**
     * Get product IDs in the user's cart.
     *
     * Migrated from get-cart-items.php
     */
    public function getCartProductIds(?string $userId, ?string $sessionId): array
    {
        $query = DB::table('carts as c')
            ->join('cart_items as ci', 'c.id', '=', 'ci.cart_id');

        if ($userId) {
            $query->where('c.user_id', $userId);
        } else {
            $query->where('c.session_id', $sessionId)
                  ->whereNull('c.user_id');
        }

        return $query->pluck('ci.product_id')->toArray();
    }
}
