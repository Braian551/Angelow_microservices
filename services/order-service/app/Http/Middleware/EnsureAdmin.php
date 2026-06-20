<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica que el usuario autenticado tenga rol de administrador.
 *
 * Valida el token JWT contra el servicio de autenticación y comprueba
 * que el rol del usuario sea 'admin' o 'super_admin'.
 */
class EnsureAdmin
{
    /**
     * Maneja la petición entrante.
     *
     * @param Request $request Petición HTTP entrante.
     * @param Closure $next Siguiente middleware o controlador.
     * @return Response Respuesta HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        // Si no hay token, rechazar la petición
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token requerido.'], 401);
        }

        $cacheKey = 'admin_token_' . hash('sha256', $token);
        $userData = Cache::get($cacheKey);

        // Si no hay datos en caché, consultar al servicio de autenticación
        if (!$userData) {
            try {
                $authUrl = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
                $meEndpoint = str_ends_with($authUrl, '/api')
                    ? "{$authUrl}/auth/me"
                    : "{$authUrl}/api/auth/me";
                $response = Http::withToken($token)->timeout(5)->get($meEndpoint);

                // Si la respuesta del servicio de auth no es exitosa, el token es inválido
                if (!$response->successful()) {
                    return response()->json(['success' => false, 'message' => 'Token invalido.'], 401);
                }

                $userData = $response->json('data') ?? $response->json('user') ?? $response->json();
                Cache::put($cacheKey, $userData, now()->addMinutes(5));
            } catch (\Throwable) {
                return response()->json(['success' => false, 'message' => 'Error de autenticacion.'], 503);
            }
        }

        // Verificar que el usuario tenga rol de administrador
        if (!in_array($userData['role'] ?? null, ['admin', 'super_admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado.'], 403);
        }

        $request->merge(['_admin_user' => $userData]);

        return $next($request);
    }
}
