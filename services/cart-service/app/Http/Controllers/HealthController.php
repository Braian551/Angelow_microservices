<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Controlador de salud del cart-service.
 * Endpoint usado por Docker Compose y balanceadores para verificar
 * que el servicio responde correctamente.
 */
class HealthController extends Controller
{
    /**
     * GET /api/health — Retorna estado OK si el servicio está operativo.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'cart-service',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
