<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Controlador de health check para el servicio de autenticación.
 *
 * Utilizado por Docker Compose y orquestadores para verificar
 * que el servicio responde correctamente antes de enrutar tráfico.
 */
class HealthController extends Controller
{
    /**
     * Responde con el estado actual del servicio.
     *
     * Devuelve nombre del servicio, estado "ok" y timestamp ISO 8601
     * para que los mecanismos de monitoreo puedan verificar disponibilidad.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'auth-service',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
