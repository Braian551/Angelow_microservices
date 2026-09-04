<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expone perfiles públicos de usuarios para comunicación interna entre servicios.
 *
 * Endpoint de servicio a servicio (no expuesto al frontend público).
 * Permite que otros microservicios (order-service, catalog-service, etc.)
 * consulten datos básicos de usuarios por lista de IDs.
 * Protegido por token interno opcional (X-Internal-Token).
 */
class UserProfileController extends Controller
{
    /**
     * Retorna perfiles básicos de usuarios dado un listado de IDs.
     *
     * GET /api/internal/users/profiles?ids=id1,id2,id3
     * Límite de 200 IDs por consulta.
     * Si no hay token configurado en services.internal.api_token,
     * el acceso es abierto (para entornos locales/desarrollo).
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->hasInternalAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $data = $request->validate([
            'ids' => ['nullable'],
            'role' => ['nullable', 'in:customer,admin,courier,repartidor'],
        ]);
        $userIds = $this->parseUserIds($data['ids'] ?? null);
        $role = $data['role'] ?? null;
        if ($userIds === [] && $role === null) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $query = User::query();
        if ($userIds !== []) {
            $query->whereIn('id', $userIds);
        }
        if ($role !== null) {
            $query->where('role', $role);
        }
        $users = $query->limit(200)->get(['id', 'name', 'email', 'phone', 'image', 'role']);

        $profiles = $users->map(function (User $user): array {
            return [
                'id' => (string) $user->id,
                'name' => $this->resolveUserName($user->name, $user->id),
                'email' => $this->normalizeUserEmail($user->email),
                'phone' => $this->normalizeUserPhone($user->phone),
                'image' => $this->normalizeUserImagePath($user->image),
                'role' => $user->role,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    /**
     * Valida token interno opcional para endpoints de servicio a servicio.
     *
     * Si no hay token configurado (vacío), permite acceso libre.
     * La comparación usa hash_equals para prevención de timing attacks.
     */
    private function hasInternalAccess(Request $request): bool
    {
        $expectedToken = trim((string) config('services.internal.api_token', ''));
        if ($expectedToken === '') {
            return true;
        }

        $providedToken = trim((string) $request->header('X-Internal-Token', ''));
        if ($providedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $providedToken);
    }

    /**
     * Normaliza entrada de IDs en formato CSV o arreglo y elimina duplicados.
     *
     * Límite de 200 IDs para evitar sobrecarga en la consulta.
     */
    private function parseUserIds(mixed $rawIds): array
    {
        $items = [];

        if (is_string($rawIds)) {
            $items = explode(',', $rawIds);
        } elseif (is_array($rawIds)) {
            $items = $rawIds;
        }

        $normalized = [];
        foreach ($items as $item) {
            $id = trim((string) $item);
            if ($id === '' || strlen($id) > 64) {
                continue;
            }
            $normalized[$id] = true;
            if (count($normalized) >= 200) {
                break;
            }
        }

        return array_keys($normalized);
    }

    /**
     * Retorna nombre visible con fallback si está vacío.
     *
     * Si no hay nombre, intenta mostrar "Usuario {id}".
     * Si tampoco hay ID, retorna "Usuario" genérico.
     */
    private function resolveUserName(?string $name, ?string $userId = null): string
    {
        $cleanName = trim((string) $name);
        if ($cleanName !== '') {
            return $cleanName;
        }

        $cleanUserId = trim((string) $userId);
        if ($cleanUserId === '') {
            return 'Usuario';
        }

        return 'Usuario ' . $cleanUserId;
    }

    /**
     * Normaliza ruta de avatar para mantener compatibilidad con frontend.
     *
     * Comportamiento heredado de LoginController::normalizeUserImagePath.
     */
    private function normalizeUserImagePath(?string $path): string
    {
        $cleanPath = trim((string) $path);
        if ($cleanPath === '') {
            return 'images/default-avatar.png';
        }

        if (str_contains($cleanPath, '/')) {
            return ltrim(str_replace('\\', '/', $cleanPath), '/');
        }

        return 'uploads/users/' . $cleanPath;
    }

    /**
     * Normaliza correo visible para consumo interno entre servicios.
     *
     * Si el correo no es válido o está vacío, retorna null
     * para que el servicio consumidor maneje el campo ausente.
     */
    private function normalizeUserEmail(?string $email): ?string
    {
        $cleanEmail = trim((string) $email);
        if ($cleanEmail === '' || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $cleanEmail;
    }

    /**
     * Normaliza teléfono y evita retornar valores vacíos.
     *
     * Si el teléfono es null o cadena vacía, retorna null.
     */
    private function normalizeUserPhone(?string $phone): ?string
    {
        $cleanPhone = trim((string) $phone);
        return $cleanPhone === '' ? null : $cleanPhone;
    }
}
