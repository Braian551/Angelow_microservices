<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Controlador admin para gestion de clientes y administradores.
 */
class AdminUserController extends Controller
{
    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    /**
     * GET /api/admin/customers
     * Lista todos los clientes con estadisticas basicas.
     */
    public function customers(Request $request): JsonResponse
    {
        $query = User::query()->where('role', 'customer');
        $likeOperator = $this->likeOperator();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('name', $likeOperator, "%{$search}%")
                  ->orWhere('email', $likeOperator, "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('created_at')
            ->limit(200)
            ->get(['id', 'name', 'email', 'phone', 'image', 'is_blocked', 'created_at', 'last_access']);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * PATCH /api/admin/customers/{id}/block
     * Bloquea o desbloquea un cliente.
     */
    public function toggleBlock(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $user->is_blocked = !$user->is_blocked;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_blocked ? 'Usuario bloqueado' : 'Usuario desbloqueado',
        ]);
    }

    /**
     * GET /api/admin/administrators
     * Lista todos los administradores.
     */
    public function administrators(): JsonResponse
    {
        $admins = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'phone', 'image', 'role', 'is_blocked', 'created_at', 'last_access']);

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    /**
     * POST /api/admin/administrators
     * Crea un nuevo administrador.
     */
    public function storeAdmin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'string', 'in:admin,super_admin'],
        ]);

        $user = User::create([
            'id' => Str::random(20),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Administrador creado',
            'data' => $user->only(['id', 'name', 'email', 'role']),
        ], 201);
    }

    /**
     * PUT /api/admin/administrators/{id}
     * Actualiza un administrador existente.
     */
    public function updateAdmin(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Administrador no encontrado'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', "unique:users,email,{$id}"],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'string', 'in:admin,super_admin'],
            'active' => ['nullable', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        if (isset($data['role'])) {
            $user->role = $data['role'];
        }
        if (isset($data['active'])) {
            $user->is_blocked = !$data['active'];
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Administrador actualizado',
        ]);
    }

    /**
     * DELETE /api/admin/administrators/{id}
     * Elimina un administrador (no puede eliminarse a si mismo).
     */
    public function destroyAdmin(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser && $currentUser->id === $id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminarte a ti mismo',
            ], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Administrador no encontrado'], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Administrador eliminado',
        ]);
    }

    /**
     * GET /api/admin/reports/customers
     * Reporte de clientes.
     */
    public function reportCustomers(Request $request): JsonResponse
    {
        $query = User::query()->where('role', 'customer');

        $totalCustomers = (clone $query)->count();
        $newCustomers = 0;
        $returningCustomers = 0;

        if ($request->filled('from')) {
            $newCustomers = (clone $query)
                ->where('created_at', '>=', $request->string('from')->toString())
                ->count();
        }

        $rows = $query->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'name', 'email', 'created_at', 'last_access'])
            ->map(static function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    // Sin dependencia cross-service: se envian valores por defecto.
                    'orders_count' => 0,
                    'total_spent' => 0,
                    'last_order_date' => $user->last_access,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'totalCustomers' => $totalCustomers,
                'newCustomers' => $newCustomers,
                'returningCustomers' => $returningCustomers,
                'rows' => $rows,
            ],
        ]);
    }
}
