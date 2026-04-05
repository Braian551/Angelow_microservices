<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminShippingController extends Controller
{
    // ── Metodos de envio ────────────────────────────────────

    public function methods(): JsonResponse
    {
        $methods = DB::table('shipping_methods')->orderBy('name')->get();

        return response()->json(['success' => true, 'data' => $methods]);
    }

    public function storeMethod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $id = DB::table('shipping_methods')->insertGetId([
            'name'       => $data['name'],
            'is_active'  => $data['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Metodo creado.', 'id' => $id], 201);
    }

    public function updateMethod(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [];
        if (isset($data['name']))      $payload['name']      = $data['name'];
        if (isset($data['is_active'])) $payload['is_active'] = $data['is_active'];
        $payload['updated_at'] = now();

        $updated = DB::table('shipping_methods')->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Metodo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Metodo actualizado.']);
    }

    public function destroyMethod(int $id): JsonResponse
    {
        $deleted = DB::table('shipping_methods')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Metodo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Metodo eliminado.']);
    }

    // ── Reglas de precio ────────────────────────────────────

    public function rules(): JsonResponse
    {
        $rules = DB::table('shipping_price_rules')->orderBy('min_price')->get();

        return response()->json(['success' => true, 'data' => $rules]);
    }

    public function storeRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'min_price'     => ['required', 'numeric', 'min:0'],
            'max_price'     => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $id = DB::table('shipping_price_rules')->insertGetId([
            'min_price'     => $data['min_price'],
            'max_price'     => $data['max_price'] ?? null,
            'shipping_cost' => $data['shipping_cost'],
            'is_active'     => $data['is_active'] ?? true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Regla creada.', 'id' => $id], 201);
    }

    public function updateRule(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'min_price'     => ['sometimes', 'numeric', 'min:0'],
            'max_price'     => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['sometimes', 'numeric', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $payload = [];
        if (isset($data['min_price']))     $payload['min_price']     = $data['min_price'];
        if (isset($data['max_price']))     $payload['max_price']     = $data['max_price'];
        if (isset($data['shipping_cost'])) $payload['shipping_cost'] = $data['shipping_cost'];
        if (isset($data['is_active']))     $payload['is_active']     = $data['is_active'];
        $payload['updated_at'] = now();

        $updated = DB::table('shipping_price_rules')->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla actualizada.']);
    }

    public function destroyRule(int $id): JsonResponse
    {
        $deleted = DB::table('shipping_price_rules')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
    }
}
