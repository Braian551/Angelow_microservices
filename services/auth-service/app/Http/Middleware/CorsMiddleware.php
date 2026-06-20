<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware CORS para permitir peticiones desde el frontend Vue.js.
 *
 * Configura los encabezados CORS necesarios para que la SPA de Vue
 * (http://localhost:5173) pueda consumir la API del auth-service.
 * Maneja adecuadamente las peticiones preflight OPTIONS.
 */
class CorsMiddleware
{
    /**
     * Maneja la petición y agrega encabezados CORS.
     *
     * Las peticiones OPTIONS (preflight) se responden con 204 sin
     * procesar el resto del middleware. Los orígenes permitidos
     * están definidos en $allowedOrigins.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];

        $origin = $request->header('Origin');

        // Maneja peticiones preflight OPTIONS
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
