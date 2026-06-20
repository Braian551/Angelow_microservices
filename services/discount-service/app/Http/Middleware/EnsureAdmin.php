<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica que la solicitud tenga un token de administrador válido.
 * Consulta el auth-service para validar el token, cachea el resultado 5 minutos
 * e inyecta los datos del usuario admin en la request para uso posterior.
 */
class EnsureAdmin
{
    /**
     * Valida el token Bearer, consulta el auth-service, cachea el perfil
     * y verifica que el rol sea admin o super_admin antes de continuar.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extrae el token Bearer del encabezado de autorización.
        $token = $request->bearerToken();

        // Si no hay token Bearer en la solicitud, rechaza con 401.
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token requerido.'], 401);
        }

        // Clave de caché única por token para evitar consultas repetidas al auth-service.
        $cacheKey = 'admin_token_' . hash('sha256', $token);
        $userData = Cache::get($cacheKey);

        if (!$userData) {
            try {
                // Construye la URL completa del endpoint /me del auth-service.
                $authUrl = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
                $meEndpoint = str_ends_with($authUrl, '/api')
                    ? "{$authUrl}/auth/me"
                    : "{$authUrl}/api/auth/me";
                $response = Http::withToken($token)->timeout(5)->get($meEndpoint);

                // Si el auth-service rechaza el token, la respuesta no es exitosa.
                if (!$response->successful()) {
                    return response()->json(['success' => false, 'message' => 'Token invalido.'], 401);
                }

                // Extrae datos del usuario desde distintas estructuras posibles de respuesta.
                // Intenta distintos formatos de respuesta según la versión del auth-service.
                $userData = $response->json('data') ?? $response->json('user') ?? $response->json();
                // Almacena en caché por 5 minutos para evitar consultas repetidas.
                Cache::put($cacheKey, $userData, now()->addMinutes(5));
            } catch (\Throwable) {
                // Error de conexión con auth-service: no se puede verificar el token.
                return response()->json(['success' => false, 'message' => 'Error de autenticacion.'], 503);
            }
        }

        // Verifica que el rol del usuario autenticado sea administrativo.
        if (!in_array($userData['role'] ?? null, ['admin', 'super_admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado.'], 403);
        }

        // Inyecta los datos del admin en la request para que los controladores los usen.
        $request->merge(['_admin_user' => $userData]);

        return $next($request);
    }
}
