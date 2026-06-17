<?php

namespace App\Http\Controllers;

// Comentario de mantenimiento: Este controlador expone endpoints HTTP y delega la lógica de negocio al dominio correspondiente.

use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product Controller
 *
 * Handles API requests related to browsing and viewing products.
 */
class ProductController extends Controller
{
    /**
     * Explica la intención de __construct dentro del flujo del servicio.
     */
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    /**
     * GET /api/products
     *
     * List products with optional filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'category', 'gender',
            'min_price', 'max_price', 'offers',
            'collection', 'sort',
        ]);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 12)));
        $userId = $request->query('user_id');
        $userEmail = $request->query('user_email');

        $result = $this->catalogService->listProducts($filters, $page, $perPage, $userId, $userEmail);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * GET /api/products/{slug}
     *
     * Get detailed product information.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $detail = $this->catalogService->getProductDetail(
                $slug,
                $request->query('user_id'),
                $request->query('user_email'),
            );

            return response()->json([
                'success' => true,
                'data' => $detail,
            ]);
        } catch (\App\Exceptions\NotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
