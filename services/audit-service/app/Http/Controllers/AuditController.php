<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Controlador principal del servicio de auditoría (audit-service).
 *
 * Expone endpoints de solo lectura para consultar la trazabilidad
 * de órdenes, usuarios y productos. Cada método consulta directamente
 * la tabla de auditoría correspondiente usando Query Builder, ya que
 * estas tablas no tienen modelo Eloquent propio (son tablas de registro
 * append-only heredadas del sistema legacy).
 */
class AuditController extends Controller
{
    /**
     * Retorna las últimas 200 entradas de auditoría de órdenes.
     *
     * Ordena por fecha descendente para mostrar primero los eventos
     * más recientes. La tabla `audit_orders` registra cada cambio
     * significativo sobre pedidos (creación, actualización, eliminación).
     */
    public function orders(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('audit_orders')->orderByDesc('fecha')->limit(200)->get(),
        ]);
    }

    /**
     * Retorna las últimas 200 entradas de auditoría de usuarios.
     *
     * La tabla `audit_users` almacena eventos como creación, modificación
     * o eliminación de cuentas de usuario, incluyendo quién realizó la acción.
     */
    public function users(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('audit_users')->orderByDesc('fecha')->limit(200)->get(),
        ]);
    }

    /**
     * Retorna las últimas 200 entradas de auditoría de productos.
     *
     * La tabla `productos_auditoria` (nomenclatura legacy) guarda la
     * trazabilidad de altas, bajas y cambios en el catálogo de productos.
     */
    public function products(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('productos_auditoria')->orderByDesc('created_at')->limit(200)->get(),
        ]);
    }
}
