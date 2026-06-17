<?php

namespace App\Http\Controllers;

use App\Services\NotificationDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Controlador principal del dominio de notificaciones.
 * Gestiona el CRUD de notificaciones, marcado como leído,
 * eliminación y disparo masivo de eventos (trigger) para campañas.
 * Soporta doble origen de datos: microservicio y legacy durante la migración.
 */
class NotificationController extends Controller
{
    /** Conexión legacy durante la migración. */
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public function __construct(
        private readonly NotificationDispatchService $dispatchService,
    ) {}

    /**
     * Lista las notificaciones de un usuario, resolviendo por user_id o user_email.
     * Primero consulta en la base local del microservicio; si no encuentra,
     * hace fallback a la base legacy.
     */
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

    /**
     * Crea una nueva notificación para un usuario y la encola para envío
     * por push y/o email según las preferencias del usuario y los canales solicitados.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'type_id' => ['nullable', 'integer'],
            'event_key' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
            'related_entity_type' => ['nullable', 'string', 'max:30'],
            'related_entity_id' => ['nullable', 'integer'],
            'send_push' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $resolvedUserId = $this->resolvePreferredUserId(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if ($resolvedUserId === null) {
            return response()->json([
                'message' => 'Debe enviar user_id o user_email',
            ], 422);
        }

        $result = $this->dispatchService->dispatchToUser([
            'user_id' => $resolvedUserId,
            'user_email' => $this->nullableString($data['user_email'] ?? null),
            'type_id' => isset($data['type_id']) ? (int) $data['type_id'] : null,
            'event_key' => $this->nullableString($data['event_key'] ?? null),
            'title' => $data['title'],
            'message' => $data['message'],
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'related_entity_id' => $data['related_entity_id'] ?? null,
            'send_push' => array_key_exists('send_push', $data) ? (bool) $data['send_push'] : true,
            'send_email' => (bool) ($data['send_email'] ?? false),
        ]);

        $status = $result['skipped'] ? 202 : 201;

        return response()->json([
            'message' => $result['skipped']
                ? 'Notificacion omitida por preferencias o canales inactivos'
                : 'Notificacion creada y encolada',
            'id' => $result['notification_id'],
            'notification_sent' => $result['notification_sent'],
            'email_sent' => $result['email_sent'],
            'skipped' => $result['skipped'],
            'reason' => $result['reason'],
        ], $status);
    }

    /**
     * Disparo interno por lotes para eventos del cliente (producto/oferta/carrito).
     * Usado por otros microservicios para enviar notificaciones masivas.
     * Requiere token interno de acceso (X-Internal-Token).
     */
    public function dispatchTrigger(Request $request): JsonResponse
    {
        if (!$this->hasInternalAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $data = $request->validate([
            'event_key' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
            'related_entity_type' => ['nullable', 'string', 'max:30'],
            'related_entity_id' => ['nullable', 'integer'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['string', 'max:64'],
            'send_push' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $limit = (int) ($data['limit'] ?? 300);
        $targetUserIds = $this->dispatchService->resolveTargetUserIds($data['user_ids'] ?? null, $limit);

        if ($targetUserIds === []) {
            return response()->json([
                'success' => false,
                'message' => 'No hay usuarios destino para el disparador.',
                'data' => [
                    'summary' => [
                        'total_candidates' => 0,
                        'notifications' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
                        'emails' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
                    ],
                ],
            ], 422);
        }

        $summary = [
            'total_candidates' => count($targetUserIds),
            'notifications' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
            'emails' => ['sent' => 0, 'failed' => 0, 'skipped' => 0],
        ];

        foreach ($targetUserIds as $userId) {
            $result = $this->dispatchService->dispatchToUser([
                'user_id' => $userId,
                'event_key' => $data['event_key'],
                'title' => $data['title'],
                'message' => $data['message'],
                'related_entity_type' => $data['related_entity_type'] ?? null,
                'related_entity_id' => $data['related_entity_id'] ?? null,
                'send_push' => array_key_exists('send_push', $data) ? (bool) $data['send_push'] : true,
                'send_email' => (bool) ($data['send_email'] ?? true),
            ]);

            if ($result['notification_sent']) {
                $summary['notifications']['sent']++;
            } elseif ($result['skipped']) {
                $summary['notifications']['skipped']++;
            } else {
                $summary['notifications']['failed']++;
            }

            if ($result['email_sent']) {
                $summary['emails']['sent']++;
            } elseif ($result['skipped'] || ($result['reason'] ?? '') === 'email_not_resolved') {
                $summary['emails']['skipped']++;
            } else {
                $summary['emails']['failed']++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Disparador procesado.',
            'data' => [
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Marca todas las notificaciones de un usuario como leídas.
     * Aplica tanto en base local como en legacy según donde estén los datos.
     */
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

    /**
     * Marca una notificación específica como leída por su ID.
     * Busca primero en local y luego en legacy.
     */
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

    /**
     * Elimina una notificación específica verificando la pertenencia al usuario.
     */
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

    /**
     * Obtiene las notificaciones de un usuario desde una conexión específica.
     */
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

    /**
     * Obtiene notificaciones para múltiples IDs candidatos,
     * las fusiona eliminando duplicados y las ordena por fecha descendente.
     */
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

    /**
     * Marca todas las notificaciones no leídas de un usuario como leídas en una conexión.
     */
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

    /**
     * Marca como leídas para múltiples IDs candidatos en una conexión.
     */
    private function markAllReadForCandidates(?string $connection, array $candidateUserIds): int
    {
        $updated = 0;

        foreach ($candidateUserIds as $candidateUserId) {
            $updated += $this->markAllReadByConnection($connection, $candidateUserId);
        }

        return $updated;
    }

    /**
     * Marca una notificación individual como leída por su ID en una conexión.
     */
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

    /**
     * Elimina una notificación verificando que pertenezca al usuario en una conexión.
     */
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

    /**
     * Elimina una notificación probando con cada ID candidato hasta encontrar la correcta.
     */
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

    /**
     * Retorna el primer ID de usuario candidato o null si no hay ninguno.
     */
    private function resolvePreferredUserId(?string $userId, ?string $userEmail): ?string
    {
        $candidateUserIds = $this->buildCandidateUserIds($userId, $userEmail);
        if ($candidateUserIds === []) {
            return null;
        }

        return $candidateUserIds[0];
    }

    /**
     * Resuelve el ID de un usuario legacy a partir de su correo electrónico.
     */
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

    /**
     * Verifica que la petición tenga el token interno de servicio a servicio.
     * Si no hay token configurado, permite el acceso (entorno de desarrollo).
     */
    private function hasInternalAccess(Request $request): bool
    {
        $expectedToken = trim((string) config('services.internal.api_token', config('services.auth.internal_token', '')));
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
     * Normaliza un valor a string nullable, retornando null si está vacío.
     */
    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
