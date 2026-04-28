<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida token Bearer contra auth-service y verifica rol admin.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token requerido.'], 401);
        }

        $cacheKey = 'admin_token_' . hash('sha256', $token);
        $userData = Cache::get($cacheKey);

        if (!$userData) {
            try {
                $authUrl = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
                $meEndpoint = str_ends_with($authUrl, '/api')
                    ? "{$authUrl}/auth/me"
                    : "{$authUrl}/api/auth/me";
                $response = Http::withToken($token)->timeout(5)->get($meEndpoint);

                if (!$response->successful()) {
                    return response()->json(['success' => false, 'message' => 'Token invalido.'], 401);
                }

                $userData = $response->json('data') ?? $response->json('user') ?? $response->json();
                Cache::put($cacheKey, $userData, now()->addMinutes(5));
            } catch (\Throwable) {
                return response()->json(['success' => false, 'message' => 'Error de autenticacion.'], 503);
            }
        }

        if (!in_array($userData['role'] ?? null, ['admin', 'super_admin'], true)) {
            return response()->json(['success' => false, 'message' => 'Acceso denegado.'], 403);
        }

        $request->merge(['_admin_user' => $userData]);

        return $next($request);
    }
}
