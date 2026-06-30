<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica que la solicitud provenga de un usuario administrador.
 *
 * Extrae el token Bearer de la cabecera Authorization, lo valida contra
 * auth-service (endpoint /api/auth/me) y verifica que el rol sea 'admin'
 * o 'super_admin'. Los resultados se cachean por 5 minutos para reducir
 * la carga sobre auth-service.
 *
 * En caso de error de conexión con auth-service, retorna 503 Service Unavailable
 * para evitar falsos positivos de autenticación cuando el servicio de auth
 * no está disponible.
 */
class EnsureAdmin
{
    /**
     * Maneja la solicitud entrante.
     *
     * Flujo:
     * 1. Extrae el token Bearer.
     * 2. Revisa caché de token.
     * 3. Si no está en caché, valida contra auth-service.
     * 4. Verifica rol de administrador.
     * 5. Inyecta datos del admin en la request para uso posterior.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        // Si no hay token, la solicitud no está autenticada
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token requerido.'], 401);
        }

        // Clave de caché basada en hash del token para evitar almacenar tokens en texto plano
        $cacheKey = 'admin_token_' . hash('sha256', $token);
        $userData = Cache::get($cacheKey);

        if (!$userData) {
            try {
                // Construye la URL del endpoint de verificación según configuración
                $authUrl = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
                $meEndpoint = str_ends_with($authUrl, '/api')
                    ? "{$authUrl}/auth/me"
                    : "{$authUrl}/api/auth/me";

                // Valida el token contra auth-service con timeout de 5s
                $response = Http::withToken($token)->timeout(5)->get($meEndpoint);

                if (!$response->successful()) {
                    return response()->json(['success' => false, 'message' => 'Token inválido.'], 401);
                }

                // Extrae datos del usuario (soporta diferentes estructuras de respuesta)
                $userData = $response->json('data') ?? $response->json('user') ?? $response->json();

                // Almacena en caché por 5 minutos para reducir latencia
                Cache::put($cacheKey, $userData, now()->addMinutes(5));
            } catch (\Throwable) {
                // Si auth-service no responde, no se puede verificar la identidad
                return response()->json(['success' => false, 'message' => 'Error de autenticación.'], 503);
            }
        }

        // Verifica que el rol sea administrador o superadministrador
        if (!in_array($userData['role'] ?? null, ['admin', 'super_admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado.'], 403);
        }

        // Inyecta los datos del admin en la request para uso en controladores
        $request->merge(['_admin_user' => $userData]);

        return $next($request);
    }
}
