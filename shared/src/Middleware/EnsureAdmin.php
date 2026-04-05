<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que valida el token Bearer contra auth-service
 * y verifica que el usuario tenga rol admin.
 * Cachea la validacion 5 minutos para reducir llamadas inter-servicio.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticacion requerido.',
            ], 401);
        }

        $cacheKey = 'admin_token_' . hash('sha256', $token);
        $userData = Cache::get($cacheKey);

        if (!$userData) {
            try {
                $authUrl = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
                $meEndpoint = str_ends_with($authUrl, '/api')
                    ? "{$authUrl}/auth/me"
                    : "{$authUrl}/api/auth/me";
                $response = Http::withToken($token)
                    ->timeout(5)
                    ->get($meEndpoint);

                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token invalido o expirado.',
                    ], 401);
                }

                $userData = $response->json('data') ?? $response->json('user') ?? $response->json();
                Cache::put($cacheKey, $userData, now()->addMinutes(5));
            } catch (\Throwable) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo validar la autenticacion.',
                ], 503);
            }
        }

        $role = $userData['role'] ?? null;

        if (!in_array($role, ['admin', 'super_admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Se requiere rol de administrador.',
            ], 403);
        }

        // Agregar datos del admin al request para uso en controladores
        $request->merge(['_admin_user' => $userData]);

        return $next($request);
    }
}
