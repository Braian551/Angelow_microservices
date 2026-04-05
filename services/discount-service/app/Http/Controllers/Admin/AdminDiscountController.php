<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDiscountController extends Controller
{
    // ── Codigos de descuento ────────────────────────────────

    public function codes(): JsonResponse
    {
        $codes = DB::table('discount_codes')->orderByDesc('created_at')->get();

        return response()->json(['success' => true, 'data' => $codes]);
    }

    public function storeCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'             => ['required', 'string', 'max:20'],
            'discount_type_id' => ['required', 'integer'],
            'discount_value'   => ['required', 'numeric', 'min:0'],
            'max_uses'         => ['nullable', 'integer', 'min:1'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date'],
            'is_active'        => ['nullable', 'boolean'],
            'is_single_use'    => ['nullable', 'boolean'],
        ]);

        $admin = $request->input('_admin_user', []);

        $id = DB::table('discount_codes')->insertGetId([
            'code'             => strtoupper($data['code']),
            'discount_type_id' => $data['discount_type_id'],
            'discount_value'   => $data['discount_value'],
            'max_uses'         => $data['max_uses'] ?? null,
            'used_count'       => 0,
            'start_date'       => $data['start_date'] ?? null,
            'end_date'         => $data['end_date'] ?? null,
            'is_active'        => $data['is_active'] ?? true,
            'is_single_use'    => $data['is_single_use'] ?? false,
            'created_by'       => $admin['id'] ?? null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Codigo creado.', 'id' => $id], 201);
    }

    public function updateCode(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code'             => ['sometimes', 'string', 'max:20'],
            'discount_type_id' => ['sometimes', 'integer'],
            'discount_value'   => ['sometimes', 'numeric', 'min:0'],
            'max_uses'         => ['nullable', 'integer', 'min:1'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date'],
            'is_active'        => ['nullable', 'boolean'],
            'is_single_use'    => ['nullable', 'boolean'],
        ]);

        $payload = array_filter([
            'code'             => isset($data['code']) ? strtoupper($data['code']) : null,
            'discount_type_id' => $data['discount_type_id'] ?? null,
            'discount_value'   => $data['discount_value'] ?? null,
            'max_uses'         => $data['max_uses'] ?? null,
            'start_date'       => $data['start_date'] ?? null,
            'end_date'         => $data['end_date'] ?? null,
            'is_active'        => $data['is_active'] ?? null,
            'is_single_use'    => $data['is_single_use'] ?? null,
        ], fn($v) => $v !== null);

        $payload['updated_at'] = now();

        $updated = DB::table('discount_codes')->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Codigo actualizado.']);
    }

    public function destroyCode(int $id): JsonResponse
    {
        $deleted = DB::table('discount_codes')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Codigo eliminado.']);
    }

    // ── Descuentos por volumen ──────────────────────────────

    public function bulkDiscounts(): JsonResponse
    {
        $rules = DB::table('bulk_discount_rules')->orderBy('min_quantity')->get();

        return response()->json(['success' => true, 'data' => $rules]);
    }

    public function storeBulkDiscount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'min_quantity'        => ['required', 'integer', 'min:1'],
            'max_quantity'        => ['nullable', 'integer', 'min:1'],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $id = DB::table('bulk_discount_rules')->insertGetId([
            'min_quantity'        => $data['min_quantity'],
            'max_quantity'        => $data['max_quantity'] ?? null,
            'discount_percentage' => $data['discount_percentage'],
            'is_active'           => $data['is_active'] ?? true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Regla creada.', 'id' => $id], 201);
    }

    public function updateBulkDiscount(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'min_quantity'        => ['sometimes', 'integer', 'min:1'],
            'max_quantity'        => ['nullable', 'integer', 'min:1'],
            'discount_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $payload = array_filter([
            'min_quantity'        => $data['min_quantity'] ?? null,
            'max_quantity'        => $data['max_quantity'] ?? null,
            'discount_percentage' => $data['discount_percentage'] ?? null,
            'is_active'           => $data['is_active'] ?? null,
        ], fn($v) => $v !== null);

        $payload['updated_at'] = now();

        $updated = DB::table('bulk_discount_rules')->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla actualizada.']);
    }

    public function destroyBulkDiscount(int $id): JsonResponse
    {
        $deleted = DB::table('bulk_discount_rules')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
    }
}
