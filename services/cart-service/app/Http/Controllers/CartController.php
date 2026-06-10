<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de carrito
 *
 * Atiende solicitudes API para operaciones del carrito de compras.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * GET /api/cart
     *
     * Obtiene los ítems actuales del carrito con detalle y totales.
     */
    public function index(Request $request): JsonResponse
    {
        $userId    = $request->query('user_id');
        $sessionId = $request->query('session_id');

        if (!$userId && !$sessionId) {
            return response()->json([
                'success' => false,
                'error'   => 'Se requiere user_id o session_id',
            ], 400);
        }

        $cart = $this->cartService->getCartItems($userId, $sessionId);

        return response()->json([
            'success' => true,
            'data'    => $cart,
        ]);
    }

    /**
     * POST /api/cart/add
     *
     * Agrega una variante de producto al carrito.
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'       => 'required|integer',
            'size_variant_id'  => 'required|integer',
            'color_variant_id' => 'nullable|integer',
            'quantity'         => 'nullable|integer|min:1',
            'user_id'          => 'nullable|string',
            'session_id'       => 'nullable|string',
        ], [
            'quantity.integer' => 'La cantidad debe ser un número entero mayor o igual a 1.',
            'quantity.min' => 'La cantidad debe ser un número entero mayor o igual a 1.',
        ]);

        try {
            $result = $this->cartService->addToCart(
                $request->input('user_id'),
                $request->input('session_id'),
                $request->input('product_id'),
                $request->input('color_variant_id'),
                $request->input('size_variant_id'),
                $request->input('quantity', 1),
            );

            return response()->json([
                'success' => true,
                ...$result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * PUT /api/cart/{itemId}
     *
     * Actualiza la cantidad de un ítem del carrito.
     */
    public function update(Request $request, int $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ], [
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero mayor o igual a 1.',
            'quantity.min' => 'La cantidad debe ser un número entero mayor o igual a 1.',
        ]);

        try {
            $this->cartService->updateQuantity($itemId, $request->input('quantity'));

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/cart/{itemId}
     *
     * Elimina un ítem del carrito.
     */
    public function destroy(int $itemId): JsonResponse
    {
        $this->cartService->removeFromCart($itemId);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
        ]);
    }

    /**
     * GET /api/cart/items
     *
     * Obtiene identificadores de productos del carrito para consultas ligeras.
     */
    public function productIds(Request $request): JsonResponse
    {
        $userId    = $request->query('user_id');
        $sessionId = $request->query('session_id');

        $ids = $this->cartService->getCartProductIds($userId, $sessionId);

        return response()->json([
            'success' => true,
            'items'   => $ids,
        ]);
    }

    /**
     * POST /api/admin/cart/abandoned/reminders/dispatch
     *
     * Dispara recordatorios para carritos abandonados.
     */
    public function dispatchAbandonedReminders(Request $request): JsonResponse
    {
        if (!$this->hasInternalAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $data = $request->validate([
            'inactive_minutes' => ['nullable', 'integer', 'min:30', 'max:10080'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $summary = $this->cartService->dispatchAbandonedCartReminders(
            (int) ($data['inactive_minutes'] ?? 180),
            (int) ($data['limit'] ?? 120),
        );

        return response()->json([
            'success' => true,
            'message' => 'Disparo de carritos abandonados ejecutado.',
            'data' => [
                'summary' => $summary,
            ],
        ]);
    }

    private function hasInternalAccess(Request $request): bool
    {
        $expectedToken = trim((string) config('services.notifications.internal_token', env('AUTH_INTERNAL_TOKEN', '')));
        if ($expectedToken === '') {
            return true;
        }

        $providedToken = trim((string) $request->header('X-Internal-Token', ''));
        if ($providedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $providedToken);
    }
}
