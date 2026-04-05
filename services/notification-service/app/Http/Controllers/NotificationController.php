<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchNotificationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class NotificationController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json(['data' => []]);
        }

        $items = $this->fetchNotificationsForCandidates(null, $candidateUserIds);

        // Fallback temporal para alinear datos legacy durante la migración.
        if ($items->isEmpty()) {
            $items = $this->fetchNotificationsForCandidates(self::LEGACY_CONNECTION, $candidateUserIds);
        }

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'string', 'max:20'],
            'type_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
            'related_entity_type' => ['nullable', 'string', 'max:30'],
            'related_entity_id' => ['nullable', 'integer'],
        ]);

        $id = DB::table('notifications')->insertGetId([
            'user_id' => $data['user_id'],
            'type_id' => $data['type_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'related_entity_id' => $data['related_entity_id'] ?? null,
            'is_read' => false,
            'is_email_sent' => false,
            'is_sms_sent' => false,
            'is_push_sent' => false,
            'created_at' => now(),
        ]);

        $payload = [
            'id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'type_id' => $data['type_id'],
        ];

        DispatchNotificationJob::dispatch($id, $data['user_id'], $payload);

        DB::table('notification_queue')->insert([
            'notification_id' => $id,
            'channel' => 'push',
            'status' => 'pending',
            'attempts' => 0,
            'scheduled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notificación creada y encolada',
            'id' => $id,
        ], 201);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json([
                'message' => 'Debe enviar user_id o user_email',
                'updated' => 0,
            ], 422);
        }

        $updated = $this->markAllReadForCandidates(null, $candidateUserIds);

        if ($updated === 0) {
            $updated = $this->markAllReadForCandidates(self::LEGACY_CONNECTION, $candidateUserIds);
        }

        return response()->json([
            'message' => 'Notificaciones marcadas como leídas',
            'updated' => $updated,
        ]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $updated = $this->markOneReadByConnection(null, $id);

        if ($updated === 0) {
            $updated = $this->markOneReadByConnection(self::LEGACY_CONNECTION, $id);
        }

        if ($updated === 0) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json(['message' => 'Debe enviar user_id o user_email'], 422);
        }

        $deleted = $this->deleteForCandidates(null, $id, $candidateUserIds);

        if ($deleted === 0) {
            $deleted = $this->deleteForCandidates(self::LEGACY_CONNECTION, $id, $candidateUserIds);
        }

        if ($deleted === 0) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        return response()->json(['message' => 'Notificación eliminada']);
    }

    private function fetchNotificationsByConnection(?string $connection, string $userId): Collection
    {
        try {
            return $this->query($connection)
                ->table('notifications')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function fetchNotificationsForCandidates(?string $connection, array $candidateUserIds): Collection
    {
        $items = collect();

        foreach ($candidateUserIds as $candidateUserId) {
            $items = $items->concat($this->fetchNotificationsByConnection($connection, $candidateUserId));
        }

        return $items
            ->unique('id')
            ->sortByDesc(function ($item): int {
                $raw = $item->created_at ?? null;
                $timestamp = is_string($raw) ? strtotime($raw) : null;
                return $timestamp ?: 0;
            })
            ->values();
    }

    private function markAllReadByConnection(?string $connection, string $userId): int
    {
        try {
            return (int) $this->query($connection)
                ->table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        } catch (Throwable) {
            return 0;
        }
    }

    private function markAllReadForCandidates(?string $connection, array $candidateUserIds): int
    {
        $updated = 0;

        foreach ($candidateUserIds as $candidateUserId) {
            $updated += $this->markAllReadByConnection($connection, $candidateUserId);
        }

        return $updated;
    }

    private function markOneReadByConnection(?string $connection, int $id): int
    {
        try {
            return (int) $this->query($connection)
                ->table('notifications')
                ->where('id', $id)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        } catch (Throwable) {
            return 0;
        }
    }

    private function deleteByConnection(?string $connection, int $id, string $userId): int
    {
        try {
            return (int) $this->query($connection)
                ->table('notifications')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->delete();
        } catch (Throwable) {
            return 0;
        }
    }

    private function deleteForCandidates(?string $connection, int $id, array $candidateUserIds): int
    {
        foreach ($candidateUserIds as $candidateUserId) {
            $deleted = $this->deleteByConnection($connection, $id, $candidateUserId);
            if ($deleted > 0) {
                return $deleted;
            }
        }

        return 0;
    }

    /**
     * Construye candidatos de user_id usando id directo y resolución por correo en legacy.
     */
    private function buildCandidateUserIds(?string $userId, ?string $userEmail): array
    {
        $candidateUserIds = [];

        if ($userId !== null && $userId !== '') {
            $candidateUserIds[] = $userId;
        }

        $legacyUserId = $this->resolveLegacyUserIdByEmail($userEmail);

        if ($legacyUserId !== null && !in_array($legacyUserId, $candidateUserIds, true)) {
            $candidateUserIds[] = $legacyUserId;
        }

        return $candidateUserIds;
    }

    private function resolveLegacyUserIdByEmail(?string $userEmail): ?string
    {
        if ($userEmail === null || $userEmail === '') {
            return null;
        }

        try {
            $userId = DB::connection(self::LEGACY_CONNECTION)
                ->table('users')
                ->whereRaw('LOWER(email) = ?', [Str::lower($userEmail)])
                ->value('id');

            if ($userId === null || $userId === '') {
                return null;
            }

            return (string) $userId;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Obtiene el query builder según conexión.
     * `null` usa la conexión por defecto del microservicio.
     */
    private function query(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
