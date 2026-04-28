<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function orders(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('audit_orders')->orderByDesc('fecha')->limit(200)->get(),
        ]);
    }

    public function users(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('audit_users')->orderByDesc('fecha')->limit(200)->get(),
        ]);
    }

    public function products(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('productos_auditoria')->orderByDesc('created_at')->limit(200)->get(),
        ]);
    }
}
