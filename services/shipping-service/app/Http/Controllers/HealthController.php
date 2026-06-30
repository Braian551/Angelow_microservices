<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Controlador de verificación de salud del servicio de envíos.
 *
 * Expone un endpoint básico (/api/health) que los mecanismos de orquestación
 * (Docker, balanceadores) usan para determinar si el servicio responde.
 * Retorna estado OK más la marca de tiempo actual en formato ISO 8601.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'shipping-service',          // Nombre identificador del servicio
            'status' => 'ok',                         // Estado operativo
            'timestamp' => now()->toIso8601String(),  // Marca de tiempo actual ISO 8601
        ]);
    }
}
