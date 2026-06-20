<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Controlador de salud del servicio.
 *
 * Proporciona un endpoint para verificar que el servicio order-service
 * está operativo y respondiendo peticiones.
 */
class HealthController extends Controller
{
    /**
     * Responde con el estado de salud del servicio.
     *
     * @return JsonResponse Información del servicio y estado actual.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'order-service',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
