<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente de perfiles publicos para comunicacion interna con auth-service.
 */
class AuthProfileService
{
    /**
     * Obtiene perfiles de usuario por ids.
     *
     * @return array<string, array{id:string,name:string,image:string}>
     */
    public function getProfilesByIds(array $userIds): array
    {
        $normalizedIds = $this->normalizeUserIds($userIds);
        if ($normalizedIds === []) {
            return [];
        }

        $baseUrl = rtrim((string) config('services.auth.base_url', ''), '/');
        if ($baseUrl === '') {
            return [];
        }

        try {
            $request = Http::timeout(4)->acceptJson();

            $internalToken = trim((string) config('services.auth.internal_token', ''));
            if ($internalToken !== '') {
                $request = $request->withHeaders([
                    'X-Internal-Token' => $internalToken,
                ]);
            }

            $response = $request->get($baseUrl . '/internal/users/profiles', [
                'ids' => implode(',', $normalizedIds),
            ]);

            if (!$response->successful()) {
                Log::warning('No se pudieron obtener perfiles de auth-service', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $items = $response->json('data');
            if (!is_array($items)) {
                return [];
            }

            $profiles = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $id = trim((string) ($item['id'] ?? ''));
                if ($id === '') {
                    continue;
                }

                $profiles[$id] = [
                    'id' => $id,
                    'name' => $this->resolveUserName($item['name'] ?? null, $id),
                    'image' => $this->normalizeUserImagePath($item['image'] ?? null),
                ];
            }

            return $profiles;
        } catch (\Throwable $exception) {
            Log::warning('Error consultando perfiles internos de auth-service', [
                'message' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Limpia ids para evitar payloads ruidosos.
     *
     * @return array<int, string>
     */
    private function normalizeUserIds(array $userIds): array
    {
        $normalized = [];

        foreach ($userIds as $userId) {
            $id = trim((string) $userId);
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
     * Retorna nombre mostrado con fallback consistente.
     */
    private function resolveUserName(mixed $name, string $userId): string
    {
        $cleanName = trim((string) $name);
        if ($cleanName !== '') {
            return $cleanName;
        }

        return 'Usuario ' . $userId;
    }

    /**
     * Normaliza la ruta del avatar para compatibilidad con frontend.
     */
    private function normalizeUserImagePath(mixed $path): string
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
