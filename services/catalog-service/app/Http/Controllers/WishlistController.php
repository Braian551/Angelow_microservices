<?php

namespace App\Http\Controllers;

use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wishlist Controller
 *
 * Handles API requests for the user's wishlist/favorites.
 */
class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    /**
     * GET /api/wishlist?user_id=xxx
     *
     * Get all wishlist items for a user.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['user_id' => 'required|string']);

        $items = $this->wishlistService->getUserWishlist($request->query('user_id'));

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * POST /api/wishlist/toggle
     *
     * Toggle a product in the user's wishlist.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'    => 'required|string',
            'product_id' => 'required|integer',
        ]);

        try {
            $result = $this->wishlistService->toggle(
                $request->input('user_id'),
                $request->input('product_id'),
            );

            return response()->json([
                'success' => true,
                ...$result,
            ]);
        } catch (\App\Exceptions\NotFoundException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 404);
        }
    }
}
