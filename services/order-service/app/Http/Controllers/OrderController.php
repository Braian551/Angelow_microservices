<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('orders')->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json([
            'data' => $query->limit(50)->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $items = DB::table('order_items')->where('order_id', $id)->get();
        $history = DB::table('order_status_history')->where('order_id', $id)->orderByDesc('created_at')->get();

        return response()->json([
            'order' => $order,
            'items' => $items,
            'history' => $history,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:20'],
            'user_id' => ['nullable', 'string', 'max:20'],
            'subtotal' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $id = DB::table('orders')->insertGetId([
            'order_number' => $data['order_number'],
            'user_id' => $data['user_id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'subtotal' => $data['subtotal'],
            'discount_amount' => 0,
            'total' => $data['total'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Orden creada',
            'id' => $id,
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:20'],
            'changed_by' => ['nullable', 'string', 'max:20'],
            'changed_by_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        DB::table('orders')->where('id', $id)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        DB::table('order_status_history')->insert([
            'order_id' => $id,
            'changed_by' => $data['changed_by'] ?? null,
            'changed_by_name' => $data['changed_by_name'] ?? null,
            'change_type' => 'status_change',
            'field_changed' => 'status',
            'old_value' => $order->status,
            'new_value' => $data['status'],
            'description' => $data['description'] ?? 'Cambio de estado de la orden',
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Estado actualizado',
        ]);
    }
}
