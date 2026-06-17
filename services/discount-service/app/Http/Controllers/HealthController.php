<?php

namespace App\Http\Controllers;

// Comentario de mantenimiento: Este controlador expone endpoints HTTP y delega la lógica de negocio al dominio correspondiente.

use Illuminate\Http\JsonResponse;

/**
 * Centraliza endpoints del dominio y traduce peticiones HTTP a respuestas del servicio.
 */
class HealthController extends Controller
{
    /**
     * Explica la intención de __invoke dentro del flujo del servicio.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'discount-service',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
