<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('payment_transactions')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json([
            'data' => $query->limit(100)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'payment_proof' => ['nullable', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['nullable', 'string', 'max:30'],
        ]);

        $payload = [
            'order_id' => $data['order_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'amount' => $data['amount'],
            'reference_number' => $data['reference_number'] ?? null,
            'payment_proof' => $data['payment_proof'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('payment_transactions', 'bank_code')) {
            $payload['bank_code'] = $data['bank_code'] ?? null;
        }

        if (Schema::hasColumn('payment_transactions', 'payment_method')) {
            $payload['payment_method'] = $data['payment_method'] ?? null;
        }

        $id = DB::table('payment_transactions')->insertGetId($payload);

        return response()->json([
            'message' => 'Transaccion creada',
            'id' => $id,
        ], 201);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
            'admin_notes' => ['nullable', 'string'],
            'verified_by' => ['nullable', 'string', 'max:20'],
        ]);

        $updated = DB::table('payment_transactions')
            ->where('id', $id)
            ->update([
                'status' => $data['status'],
                'admin_notes' => $data['admin_notes'] ?? null,
                'verified_by' => $data['verified_by'] ?? null,
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Transaccion no encontrada'], 404);
        }

        return response()->json([
            'message' => 'Transaccion actualizada',
        ]);
    }

    public function banks(): JsonResponse
    {
        $banks = DB::table('colombian_banks')
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return response()->json(['data' => $banks]);
    }
}
