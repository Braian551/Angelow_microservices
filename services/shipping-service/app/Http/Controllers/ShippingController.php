<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    public function methods(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('shipping_methods')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function rules(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('shipping_price_rules')->where('is_active', true)->orderBy('min_price')->get(),
        ]);
    }

    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subtotal' => ['required', 'numeric'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $rule = DB::table('shipping_price_rules')
            ->where('is_active', true)
            ->where('min_price', '<=', $data['subtotal'])
            ->where(function ($query) use ($data) {
                $query->whereNull('max_price')->orWhere('max_price', '>=', $data['subtotal']);
            })
            ->orderByDesc('min_price')
            ->first();

        return response()->json([
            'subtotal' => $data['subtotal'],
            'city' => $data['city'] ?? null,
            'shipping_cost' => $rule?->shipping_cost ?? 0,
            'rule' => $rule,
        ]);
    }
}
