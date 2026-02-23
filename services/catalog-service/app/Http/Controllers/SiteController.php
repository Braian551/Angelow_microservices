<?php

namespace App\Http\Controllers;

use App\Services\SiteService;
use Illuminate\Http\JsonResponse;

/**
 * Site Controller
 *
 * Handles API requests for site-wide content (settings, sliders, announcements).
 */
class SiteController extends Controller
{
    public function __construct(
        private readonly SiteService $siteService,
    ) {}

    /**
     * GET /api/home
     *
     * Returns all data needed for the home page in a single call:
     * settings, sliders, top_bar announcement, promo_banner.
     */
    public function home(): JsonResponse
    {
        $data = $this->siteService->getHomeData();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/settings
     *
     * Returns site settings (logo, store name, etc.).
     */
    public function settings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->siteService->getSettings(),
        ]);
    }

    /**
     * GET /api/sliders
     *
     * Returns active sliders.
     */
    public function sliders(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->siteService->getSliders(),
        ]);
    }
}
