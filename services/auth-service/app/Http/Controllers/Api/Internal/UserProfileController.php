<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Expone perfiles publicos de usuarios para comunicacion interna entre servicios.
 */
class UserProfileController extends Controller
{
    /**
     * GET /api/internal/users/profiles?ids=id1,id2,id3
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->hasInternalAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $userIds = $this->parseUserIds($request->query('ids'));
        if ($userIds === []) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'image']);

        $profiles = $users->map(function (User $user): array {
            return [
                'id' => (string) $user->id,
                'name' => $this->resolveUserName($user->name, $user->id),
                'image' => $this->normalizeUserImagePath($user->image),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    /**
     * Valida token interno opcional para endpoints de servicio a servicio.
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
     * Normaliza entrada de ids en formato CSV o arreglo y elimina duplicados.
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
     * Retorna nombre visible con fallback.
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
}
