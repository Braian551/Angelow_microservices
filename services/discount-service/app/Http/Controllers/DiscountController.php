<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{
    public function listCodes(): JsonResponse
    {
        $codes = DB::table('discount_codes')->orderByDesc('created_at')->limit(100)->get();

        return response()->json(['data' => $codes]);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'user_id' => ['nullable', 'string', 'max:20'],
            'order_total' => ['nullable', 'numeric'],
        ]);

        $discount = DB::table('discount_codes')
            ->whereRaw('LOWER(code) = ?', [strtolower($data['code'])])
            ->where('is_active', true)
            ->first();

        if (!$discount) {
            return response()->json(['valid' => false, 'message' => 'Codigo no valido'], 404);
        }

        $isExpired = ($discount->end_date !== null) && now()->greaterThan($discount->end_date);
        if ($isExpired) {
            return response()->json(['valid' => false, 'message' => 'Codigo expirado'], 422);
        }

        if ($discount->max_uses !== null && $discount->used_count >= $discount->max_uses) {
            return response()->json(['valid' => false, 'message' => 'Codigo sin cupos'], 422);
        }

        return response()->json([
            'valid' => true,
            'discount' => $discount,
        ]);
    }
}
