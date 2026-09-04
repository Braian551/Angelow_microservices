<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourier
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token requerido.'], 401);
        }

        $cacheKey = 'courier_token_' . hash('sha256', $token);
        $user = Cache::get($cacheKey);
        if (!is_array($user)) {
            try {
                $baseUrl = rtrim((string) config('services.auth.base_url'), '/');
                $response = Http::withToken($token)->timeout(5)->get($baseUrl . '/auth/me');
                if (!$response->successful()) {
                    return response()->json(['success' => false, 'message' => 'Token inválido.'], 401);
                }
                $user = $response->json('data');
                Cache::put($cacheKey, $user, now()->addMinutes(2));
            } catch (\Throwable) {
                return response()->json(['success' => false, 'message' => 'No fue posible validar la sesión.'], 503);
            }
        }

        if (!in_array($user['role'] ?? null, ['courier', 'repartidor'], true)) {
            return response()->json(['success' => false, 'message' => 'Acceso exclusivo para repartidores.'], 403);
        }

        $request->merge(['_courier_user' => $user]);
        return $next($request);
    }
}
