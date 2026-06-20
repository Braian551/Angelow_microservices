<?php

namespace App\Repositories;

use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Implementación del repositorio de carrito con Query Builder.
 *
 * Migrada desde los endpoints legacy de angelow/tienda/api/cart/.
 * Usa DB facade en lugar de Eloquent ORM para mantener consistencia
 * con la lógica original y evitar la sobrecarga del ORM en operaciones
 * simples de carrito.
 *
 * @see CartRepositoryInterface
 * @see CartService
 */
class QueryBuilderCartRepository implements CartRepositoryInterface
{
    /**
     * Obtiene el carrito más reciente del usuario/sesión o crea uno nuevo.
     * Si se proporciona user_id, busca por usuario; si no, por session_id.
     * Al crear, si hay user_id no guarda session_id (carrito vinculado).
     */
    public function getOrCreateCart(?string $userId, ?string $sessionId): int
    {
        $query = DB::table('carts');

        // Los usuarios autenticados se agrupan por user_id; visitantes por session_id.
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $cart = $query->orderByDesc('created_at')->first();

        if ($cart) {
            return $cart->id;
        }

        // Crea un nuevo carrito si no existe uno previo para esa identidad.
        return DB::table('carts')->insertGetId([
            'user_id'    => $userId,
            'session_id' => $userId ? null : $sessionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Retorna todos los ítems de un carrito con sus IDs de producto y variantes.
     * Convierte cada registro stdClass a array para consumo en CartService.
     */
    public function getItems(int $cartId): array
    {
        // Se seleccionan solo los campos que CartService necesita para enriquecer desde catalog-service.
        return DB::table('cart_items as ci')
            ->where('ci.cart_id', $cartId)
            ->select([
                'ci.id as item_id',
                'ci.product_id',
                'ci.color_variant_id',
                'ci.size_variant_id',
                'ci.quantity',
            ])
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();
    }

    /**
     * Inserta un nuevo ítem en el carrito con los datos de producto y variantes.
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
     * Actualiza la cantidad de un ítem existente y su timestamp.
     */
    public function updateItemQuantity(int $itemId, int $quantity): void
    {
        // El timestamp permite detectar actividad reciente para recordatorios de carrito abandonado.
        DB::table('cart_items')
            ->where('id', $itemId)
            ->update([
                'quantity'   => $quantity,
                'updated_at' => now(),
            ]);
    }

    /**
     * Elimina un ítem del carrito. Operación idempotente.
     */
    public function removeItem(int $itemId): void
    {
        DB::table('cart_items')->where('id', $itemId)->delete();
    }

    /**
     * Busca un ítem por su ID. Retorna stdClass o null si no existe.
     */
    public function findItem(int $itemId): ?object
    {
        return DB::table('cart_items')->where('id', $itemId)->first();
    }

    /**
     * Busca un ítem existente que coincida con producto + variante de talla y color.
     * Si colorVariantId es null, solo busca coincidencia por producto y talla.
     * Usado por CartService::addToCart para incrementar cantidad en lugar de duplicar.
     */
    public function findExistingItem(int $cartId, int $productId, ?int $colorVariantId, int $sizeVariantId): ?object
    {
        $query = DB::table('cart_items')
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->where('size_variant_id', $sizeVariantId);

        // El color forma parte de la identidad de la línea cuando viene informado.
        if ($colorVariantId) {
            $query->where('color_variant_id', $colorVariantId);
        } else {
            // Sin color explícito se busca una línea igualmente sin color para evitar fusionar variantes distintas.
            $query->whereNull('color_variant_id');
        }

        return $query->first();
    }

    /**
     * Obtiene los IDs de productos en el carrito del usuario/sesión.
     * Para session_id, excluye carritos que ya tienen user_id asociado
     * para evitar duplicados entre sesión anónima y usuario autenticado.
     */
    public function getCartProductIds(?string $userId, ?string $sessionId): array
    {
        $query = DB::table('carts as c')
            ->join('cart_items as ci', 'c.id', '=', 'ci.cart_id');

        // La consulta sigue el mismo criterio de identidad usado al crear o recuperar el carrito.
        if ($userId) {
            $query->where('c.user_id', $userId);
        } else {
            $query->where('c.session_id', $sessionId)
                  ->whereNull('c.user_id');
        }

        return $query->pluck('ci.product_id')->toArray();
    }
}
