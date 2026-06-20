<?php

namespace App\Http\Controllers;

// Comentario de mantenimiento: Este controlador expone endpoints HTTP y delega la lógica de negocio al dominio correspondiente.

use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;

/**
 * Category Controller
 *
 * Handles API requests for categories and collections.
 */
class CategoryController extends Controller
{
    /**
     * Explica la intención de __construct dentro del flujo del servicio.
     */
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    /**
     * GET /api/categories
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->catalogService->getCategories(),
        ]);
    }

    /**
     * GET /api/collections
     */
    public function collections(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->catalogService->getCollections(),
        ]);
    }
}
