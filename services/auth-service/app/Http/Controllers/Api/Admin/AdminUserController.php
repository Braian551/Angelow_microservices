<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Controlador administrativo para gestión de clientes y administradores.
 *
 * Expone endpoints protegidos con middleware EnsureAdmin.
 * Permite listar, buscar, bloquear clientes y CRUD completo
 * de administradores, más reportes básicos.
 */
class AdminUserController extends Controller
{
    /**
     * Retorna el operador LIKE adecuado según el motor de BD.
     *
     * PostgreSQL requiere ILIKE para búsqueda case-insensitive;
     * MySQL/MariaDB usan LIKE que ya es case-insensitive por default.
     */
    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    /**
     * Lista clientes con filtros opcionales por IDs o búsqueda textual.
     *
     * GET /api/admin/customers
     * Query params opcionales: ids (CSV), search (nombre o email)
     * Límite: 200 registros.
     */
    public function customers(Request $request): JsonResponse
    {
        $query = User::query()->where('role', 'customer');
        $likeOperator = $this->likeOperator();

        // Filtro por lista específica de IDs (útil para selección desde el frontend)
        if ($request->filled('ids')) {
            $rawIds = explode(',', $request->string('ids')->toString());
            $ids = collect($rawIds)
                ->map(static fn ($id) => trim((string) $id))
                ->filter(static fn ($id) => $id !== '')
                ->unique()
                ->take(200)
                ->values();

            if ($ids->isNotEmpty()) {
                $query->whereIn('id', $ids->all());
            }
        }

        // Búsqueda textual por nombre o email
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('name', $likeOperator, "%{$search}%")
                  ->orWhere('email', $likeOperator, "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('created_at')
            ->limit(200)
            ->get(['id', 'name', 'email', 'phone', 'image', 'role', 'is_blocked', 'created_at', 'last_access']);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Bloquea o desbloquea un cliente (toggle).
     *
     * PATCH /api/admin/customers/{id}/block
     */
    public function toggleBlock(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        if ($user->role !== 'customer') {
            return response()->json(['success' => false, 'message' => 'El usuario no es un cliente'], 422);
        }

        $user->is_blocked = !$user->is_blocked;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_blocked ? 'Usuario bloqueado' : 'Usuario desbloqueado',
        ]);
    }

    /**
     * Lista todos los administradores (admin y super_admin).
     *
     * GET /api/admin/administrators
     */
    public function administrators(): JsonResponse
    {
        $admins = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'phone', 'image', 'role', 'is_blocked', 'created_at', 'last_access'])
            ->map(static function (User $admin): array {
                return [
                    'id' => (string) $admin->id,
                    'name' => (string) $admin->name,
                    'email' => (string) $admin->email,
                    'phone' => $admin->phone,
                    'image' => $admin->image,
                    'role' => $admin->role,
                    'active' => !$admin->is_blocked,
                    'is_blocked' => (bool) $admin->is_blocked,
                    'created_at' => $admin->created_at,
                    'last_access' => $admin->last_access,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    /**
     * Consulta la identidad editable de un usuario desde el panel.
     *
     * GET /api/admin/users/{id}
     */
    public function showUser(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeUser($user),
        ]);
    }

    /**
     * Actualiza la identidad, el estado y el rol de una cuenta cliente o repartidor.
     *
     * PUT /api/admin/users/{id}
     */
    public function updateUser(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta superadministradora no se puede modificar desde esta pantalla.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['sometimes', Rule::in(['customer', 'courier', 'admin'])],
            'active' => ['nullable', 'boolean'],
        ]);

        $currentUser = $request->user();
        $isCurrentUser = $currentUser && (string) $currentUser->getKey() === (string) $user->getKey();
        if ($isCurrentUser && array_key_exists('role', $data) && $data['role'] !== $user->role) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cambiar tu propio rol de administrador.',
            ], 422);
        }
        if ($isCurrentUser && array_key_exists('active', $data) && !$data['active']) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        foreach (['name', 'email', 'phone', 'role'] as $field) {
            if (array_key_exists($field, $data)) {
                $user->{$field} = $data[$field];
            }
        }
        if (array_key_exists('active', $data)) {
            $user->is_blocked = !$data['active'];
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado.',
            'data' => $this->serializeUser($user),
        ]);
    }

    /**
     * Crea un nuevo administrador.
     *
     * POST /api/admin/administrators
     * Campos: name, email, password, active (booleano opcional)
     * Genera ID aleatorio de 20 caracteres (formato legacy).
     */
    public function storeAdmin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'id' => Str::random(20),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'is_blocked' => array_key_exists('active', $data) ? !$data['active'] : false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Administrador creado',
            'data' => [
                'id' => (string) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'active' => !$user->is_blocked,
            ],
        ], 201);
    }

    /**
     * Actualiza un administrador existente.
     *
     * PUT /api/admin/administrators/{id}
     * Campos opcionales: name, email, password, active
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
        $user->role = 'admin';
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
     * Elimina un administrador (no puede eliminarse a sí mismo).
     *
     * DELETE /api/admin/administrators/{id}
     * Previene que un admin se elimine a sí mismo.
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
     * Reporte de clientes para el panel administrativo.
     *
     * GET /api/admin/reports/customers
     * Query param opcional: from (fecha ISO para filtrar nuevos clientes)
     *
     * Nota: orders_count y total_spent se envían en 0 por ahora
     * para evitar dependencia cross-service con order-service.
     * Cuando se implemente la comunicación interna, se poblarán
     * consultando el servicio de pedidos.
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
                    // Sin dependencia cross-service: se envían valores por defecto.
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

    /** @return array<string, mixed> */
    private function serializeUser(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => $user->phone,
            'image' => $user->image,
            'role' => (string) $user->role,
            'active' => !$user->is_blocked,
            'is_blocked' => (bool) $user->is_blocked,
            'created_at' => $user->created_at,
            'last_access' => $user->last_access,
        ];
    }
}
