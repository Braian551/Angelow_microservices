<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPaymentController extends Controller
{
    /**
     * Lista todas las transacciones con filtros para el admin.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('payment_transactions')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%");
                if (Schema::hasColumn('payment_transactions', 'billing_email')) {
                    $q->orWhere('billing_email', 'like', "%{$search}%");
                }
            });
        }

        $payments = $query->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data'    => $payments->items(),
            'meta'    => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }

    /**
     * Verifica/actualiza el estado de una transaccion.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status'      => ['required', 'in:approved,rejected,pending'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $admin = $request->input('_admin_user', []);

        $updated = DB::table('payment_transactions')
            ->where('id', $id)
            ->update([
                'status'      => $data['status'],
                'admin_notes' => $data['admin_notes'] ?? null,
                'verified_by' => $admin['id'] ?? null,
                'verified_at' => now(),
                'updated_at'  => now(),
            ]);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Transaccion no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Transaccion actualizada.']);
    }
}
