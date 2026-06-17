<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica que el usuario autenticado tenga rol de administrador.
 *
 * Se aplica a todas las rutas del prefijo /api/admin.
 * Permite acceso solo a usuarios con rol 'admin' o 'super_admin'.
 * Si no cumple, retorna HTTP 403 con mensaje en español.
 */
class EnsureAdmin
{
    /**
     * Maneja la petición entrante.
     *
     * Si el usuario no está autenticado o su rol no es admin/super_admin,
     * retorna error 403. En caso contrario, continúa con la petición.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Se requiere rol de administrador.',
            ], 403);
        }

        return $next($request);
    }
}
