<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;

/**
 * Category Controller
 *
 * Handles API requests for categories and collections.
 */
class CategoryController extends Controller
{
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
