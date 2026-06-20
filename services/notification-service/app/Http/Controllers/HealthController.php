<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Controlador de salud del servicio.
 * Expone un endpoint para que el orquestador (Docker/k8s) verifique
 * que el servicio de notificaciones está corriendo correctamente.
 */
class HealthController extends Controller
{
    /**
     * Responde con estado OK y la marca de tiempo actual.
     * Usado por health checks de infraestructura.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'service' => 'notification-service',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
