<?php

namespace App\Services;

use App\Jobs\DispatchNotificationJob;
use App\Models\NotificationPreference;
use App\Models\NotificationType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class NotificationDispatchService
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public const EVENT_PRODUCT = 'product';
    public const EVENT_PROMOTION = 'promotion';
    public const EVENT_CART_REMINDER = 'cart_reminder';

    /**
     * Despacha una notificacion/correo para un usuario aplicando preferencias.
     *
     * @param array{
     *   user_id:string,
     *   user_email?:string|null,
     *   type_id?:int|null,
     *   event_key?:string|null,
     *   title:string,
     *   message:string,
     *   related_entity_type?:string|null,
     *   related_entity_id?:int|null,
     *   send_push?:bool,
     *   send_email?:bool
     * } $payload
     *
     * @return array{
     *   user_id:string,
     *   event_key:?string,
     *   notification_id:?int,
     *   notification_sent:bool,
     *   email_sent:bool,
     *   skipped:bool,
     *   reason:string
     * }
     */
    public function dispatchToUser(array $payload): array
    {
        $userId = trim((string) ($payload['user_id'] ?? ''));
        if ($userId === '') {
            return $this->skipResult('', null, 'missing_user_id');
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));

        if ($title === '' || $message === '') {
            return $this->skipResult($userId, null, 'missing_title_or_message');
        }

        $explicitEvent = $this->normalizeEventKey($payload['event_key'] ?? null);
        $resolvedEvent = $explicitEvent;

        $typeId = null;
        if ($explicitEvent !== null) {
            $typeId = $this->resolveTypeIdByEvent($explicitEvent);
        } else {
            $providedTypeId = isset($payload['type_id']) ? (int) $payload['type_id'] : null;
            if ($providedTypeId !== null && $providedTypeId > 0) {
                $typeId = $providedTypeId;
                $resolvedEvent = $this->resolveEventFromTypeId($providedTypeId);
            }
        }

        if ($typeId === null || $typeId <= 0) {
            return $this->skipResult($userId, $resolvedEvent, 'notification_type_not_resolved');
        }

        $sendPush = array_key_exists('send_push', $payload)
            ? (bool) $payload['send_push']
            : true;
        $requestedEmail = (bool) ($payload['send_email'] ?? false);

        $eventEnabled = $this->isEventEnabledForUser($userId, $resolvedEvent, $typeId);
        if (!$eventEnabled) {
            return $this->skipResult($userId, $resolvedEvent, 'preference_disabled');
        }

        $globalEmailEnabled = $this->isGlobalEmailEnabledForUser($userId);
        $sendEmail = $requestedEmail && $globalEmailEnabled;

        if (!$sendPush && !$sendEmail) {
            return $this->skipResult($userId, $resolvedEvent, 'channels_disabled');
        }

        $notificationId = null;
        $notificationSent = false;
        $emailSent = false;
        $skipped = false;
        $reason = 'ok';

        try {
            if ($sendPush) {
                $notificationId = (int) DB::table('notifications')->insertGetId([
                    'user_id' => $userId,
                    'type_id' => $typeId,
                    'title' => $title,
                    'message' => $message,
                    'related_entity_type' => $this->nullableTrim($payload['related_entity_type'] ?? null),
                    'related_entity_id' => $payload['related_entity_id'] ?? null,
                    'is_read' => false,
                    'is_email_sent' => false,
                    'is_sms_sent' => false,
                    'is_push_sent' => true,
                    'created_at' => now(),
                ]);

                $jobPayload = [
                    'id' => $notificationId,
                    'title' => $title,
                    'message' => $message,
                    'type_id' => $typeId,
                ];

                DispatchNotificationJob::dispatch($notificationId, $userId, $jobPayload);

                DB::table('notification_queue')->insert([
                    'notification_id' => $notificationId,
                    'channel' => 'push',
                    'status' => 'pending',
                    'attempts' => 0,
                    'scheduled_at' => now(),
                ]);

                $notificationSent = true;
            }

            if ($sendEmail) {
                $email = $this->resolveUserEmail($userId, $payload['user_email'] ?? null);
                if ($email !== null) {
                    $name = $this->resolveUserName($userId);
                    $emailSent = $this->sendNotificationEmail($email, $name, $title, $message, $resolvedEvent);

                    if ($emailSent && $notificationId !== null) {
                        DB::table('notifications')
                            ->where('id', $notificationId)
                            ->update(['is_email_sent' => true]);
                    }
                } else {
                    $skipped = true;
                    $reason = 'email_not_resolved';
                }
            }

            return [
                'user_id' => $userId,
                'event_key' => $resolvedEvent,
                'notification_id' => $notificationId,
                'notification_sent' => $notificationSent,
                'email_sent' => $emailSent,
                'skipped' => $skipped,
                'reason' => $reason,
            ];
        } catch (Throwable $exception) {
            Log::warning('Fallo despachando notificacion para usuario.', [
                'user_id' => $userId,
                'event_key' => $resolvedEvent,
                'error' => $exception->getMessage(),
            ]);

            return [
                'user_id' => $userId,
                'event_key' => $resolvedEvent,
                'notification_id' => null,
                'notification_sent' => false,
                'email_sent' => false,
                'skipped' => false,
                'reason' => 'dispatch_failed',
            ];
        }
    }

    /**
     * Obtiene ids de usuario candidatos para campañas por evento.
     * Usa usuarios legacy para mantener continuidad durante migracion.
     */
    public function resolveTargetUserIds(?array $requestedUserIds, int $limit = 300): array
    {
        $max = max(1, min($limit, 1000));

        if (is_array($requestedUserIds) && $requestedUserIds !== []) {
            return $this->normalizeUserIds($requestedUserIds, $max);
        }

        $legacyUserIds = $this->resolveLegacyTargetUserIds($max);
        if ($legacyUserIds !== []) {
            return $legacyUserIds;
        }

        return $this->resolveLocalTargetUserIds($max);
    }

    private function resolveLegacyTargetUserIds(int $limit): array
    {
        $max = max(1, min($limit, 1000));

        try {
            if (!Schema::connection(self::LEGACY_CONNECTION)->hasTable('users')) {
                return [];
            }

            $query = DB::connection(self::LEGACY_CONNECTION)
                ->table('users')
                ->select('id')
                ->orderByDesc('id')
                ->limit($max);

            if (Schema::connection(self::LEGACY_CONNECTION)->hasColumn('users', 'role')) {
                $query->where('role', 'customer');
            }

            return $query
                ->pluck('id')
                ->map(static fn ($id): string => trim((string) $id))
                ->filter(static fn (string $id): bool => $id !== '')
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('No se pudieron resolver usuarios destino para disparadores.', [
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fallback local cuando no hay conexión legacy disponible.
     */
    private function resolveLocalTargetUserIds(int $limit): array
    {
        $max = max(1, min($limit, 1000));
        $userIds = [];

        try {
            if (Schema::hasTable('notification_preferences')) {
                $preferenceIds = DB::table('notification_preferences')
                    ->whereNotNull('user_id')
                    ->orderByDesc('id')
                    ->limit($max)
                    ->pluck('user_id');

                foreach ($preferenceIds as $rawId) {
                    $cleanId = trim((string) $rawId);
                    if ($cleanId === '') {
                        continue;
                    }

                    $userIds[$cleanId] = true;

                    if (count($userIds) >= $max) {
                        break;
                    }
                }
            }

            if (count($userIds) < $max && Schema::hasTable('notifications')) {
                $remaining = $max - count($userIds);

                $notificationIds = DB::table('notifications')
                    ->whereNotNull('user_id')
                    ->orderByDesc('id')
                    ->limit(max($remaining * 2, $remaining))
                    ->pluck('user_id');

                foreach ($notificationIds as $rawId) {
                    $cleanId = trim((string) $rawId);
                    if ($cleanId === '') {
                        continue;
                    }

                    $userIds[$cleanId] = true;

                    if (count($userIds) >= $max) {
                        break;
                    }
                }
            }

            return array_keys($userIds);
        } catch (Throwable $exception) {
            Log::warning('No se pudieron resolver usuarios destino desde tablas locales.', [
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function normalizeUserIds(array $userIds, int $limit): array
    {
        $normalized = [];

        foreach ($userIds as $userId) {
            $clean = trim((string) $userId);
            if ($clean === '' || strlen($clean) > 64) {
                continue;
            }

            $normalized[$clean] = true;

            if (count($normalized) >= max(1, min($limit, 1000))) {
                break;
            }
        }

        return array_keys($normalized);
    }

    private function resolveTypeIdByEvent(string $eventKey): ?int
    {
        $baseTypeName = match ($eventKey) {
            self::EVENT_PRODUCT => 'product',
            self::EVENT_PROMOTION => 'promotion',
            self::EVENT_CART_REMINDER => 'order',
            default => null,
        };

        if ($baseTypeName === null) {
            return null;
        }

        try {
            $existing = NotificationType::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($baseTypeName)])
                ->first();

            if ($existing) {
                if (!$existing->is_active) {
                    $existing->is_active = true;
                    $existing->updated_at = now();
                    $existing->save();
                }

                return (int) $existing->id;
            }

            $created = NotificationType::query()->create([
                'name' => $baseTypeName,
                'description' => $this->defaultTypeDescription($eventKey),
                'is_active' => true,
            ]);

            return (int) $created->id;
        } catch (Throwable $exception) {
            Log::warning('No se pudo resolver tipo de notificación en legacy; se intentará fallback local.', [
                'event_key' => $eventKey,
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->resolveTypeIdByEventLocal($baseTypeName, $eventKey);
    }

    private function resolveTypeIdByEventLocal(string $baseTypeName, string $eventKey): ?int
    {
        try {
            if (!Schema::hasTable('notification_types')) {
                return null;
            }

            $existing = DB::table('notification_types')
                ->select('id', 'is_active')
                ->whereRaw('LOWER(name) = ?', [Str::lower($baseTypeName)])
                ->first();

            if ($existing) {
                if (!(bool) ($existing->is_active ?? true)) {
                    DB::table('notification_types')
                        ->where('id', $existing->id)
                        ->update(['is_active' => true]);
                }

                return (int) $existing->id;
            }

            $payload = [
                'name' => $baseTypeName,
                'description' => $this->defaultTypeDescription($eventKey),
                'is_active' => true,
            ];

            if (Schema::hasColumn('notification_types', 'created_at')) {
                $payload['created_at'] = now();
            }

            if (Schema::hasColumn('notification_types', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            return (int) DB::table('notification_types')->insertGetId($payload);
        } catch (Throwable $exception) {
            Log::warning('No se pudo resolver tipo de notificación en fallback local.', [
                'event_key' => $eventKey,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function defaultTypeDescription(string $eventKey): string
    {
        return match ($eventKey) {
            self::EVENT_PRODUCT => 'Nuevos productos disponibles',
            self::EVENT_PROMOTION => 'Promociones y ofertas',
            self::EVENT_CART_REMINDER => 'Recordatorios de carrito abandonado',
            default => 'Notificacion del sistema',
        };
    }

    private function resolveEventFromTypeId(int $typeId): ?string
    {
        $typeName = null;

        try {
            $type = NotificationType::query()->find($typeId);
            if ($type) {
                $typeName = Str::lower(trim((string) $type->name));
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo resolver evento desde type_id en legacy; se intentará fallback local.', [
                'type_id' => $typeId,
                'error' => $exception->getMessage(),
            ]);
        }

        if (($typeName === null || $typeName === '') && Schema::hasTable('notification_types')) {
            try {
                $rawName = DB::table('notification_types')
                    ->where('id', $typeId)
                    ->value('name');

                $typeName = Str::lower(trim((string) ($rawName ?? '')));
            } catch (Throwable $exception) {
                Log::warning('No se pudo resolver evento desde type_id en fallback local.', [
                    'type_id' => $typeId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($typeName === '') {
            return null;
        }

        if (Str::contains($typeName, ['product', 'producto'])) {
            return self::EVENT_PRODUCT;
        }

        if (Str::contains($typeName, ['promotion', 'promo', 'discount', 'oferta'])) {
            return self::EVENT_PROMOTION;
        }

        if (Str::contains($typeName, ['cart', 'carrito'])) {
            return self::EVENT_CART_REMINDER;
        }

        return null;
    }

    private function normalizeEventKey(?string $eventKey): ?string
    {
        $normalized = Str::lower(trim((string) ($eventKey ?? '')));

        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'product', 'new_product', 'nuevo_producto', 'nuevo-producto' => self::EVENT_PRODUCT,
            'promotion', 'promo', 'discount', 'offer', 'oferta', 'ofertas' => self::EVENT_PROMOTION,
            'cart_reminder', 'abandoned_cart', 'carrito_abandonado', 'carrito-abandonado', 'cart' => self::EVENT_CART_REMINDER,
            default => null,
        };
    }

    private function isEventEnabledForUser(string $userId, ?string $eventKey, int $typeId): bool
    {
        if ($eventKey === null) {
            return true;
        }

        $eventTypeId = $this->resolveTypeIdByEvent($eventKey);
        if ($eventTypeId === null) {
            return true;
        }

        $preference = null;

        try {
            $preference = NotificationPreference::query()
                ->where('user_id', $userId)
                ->where('type_id', $eventTypeId)
                ->first();
        } catch (Throwable $exception) {
            Log::warning('No se pudieron consultar preferencias en legacy; se intentará fallback local.', [
                'user_id' => $userId,
                'event_key' => $eventKey,
                'error' => $exception->getMessage(),
            ]);
        }

        if ($preference === null && Schema::hasTable('notification_preferences')) {
            try {
                $preference = DB::table('notification_preferences')
                    ->select('push_enabled')
                    ->where('user_id', $userId)
                    ->where('type_id', $eventTypeId)
                    ->first();
            } catch (Throwable $exception) {
                Log::warning('No se pudieron consultar preferencias en fallback local.', [
                    'user_id' => $userId,
                    'event_key' => $eventKey,
                    'error' => $exception->getMessage(),
                ]);

                return true;
            }
        }

        if (!$preference) {
            return true;
        }

        return (bool) ($preference->push_enabled ?? true);
    }

    private function isGlobalEmailEnabledForUser(string $userId): bool
    {
        try {
            $preferences = NotificationPreference::query()
                ->where('user_id', $userId)
                ->get(['email_enabled']);

            if (!$preferences->isEmpty()) {
                return (bool) $preferences->pluck('email_enabled')->min();
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo consultar email_enabled en legacy; se intentará fallback local.', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
        }

        if (Schema::hasTable('notification_preferences')) {
            try {
                $localPreferences = DB::table('notification_preferences')
                    ->where('user_id', $userId)
                    ->pluck('email_enabled');

                if ($localPreferences->isNotEmpty()) {
                    return (bool) $localPreferences->min();
                }
            } catch (Throwable $exception) {
                Log::warning('No se pudo consultar email_enabled en fallback local.', [
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return true;
    }

    private function resolveUserEmail(string $userId, ?string $fallbackEmail): ?string
    {
        $candidate = trim((string) ($fallbackEmail ?? ''));
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return $candidate;
        }

        try {
            if (!Schema::connection(self::LEGACY_CONNECTION)->hasTable('users')) {
                return null;
            }

            $email = DB::connection(self::LEGACY_CONNECTION)
                ->table('users')
                ->where('id', $userId)
                ->value('email');

            $cleanEmail = trim((string) ($email ?? ''));
            if ($cleanEmail === '' || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            return $cleanEmail;
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveUserName(string $userId): string
    {
        try {
            if (!Schema::connection(self::LEGACY_CONNECTION)->hasTable('users')) {
                return 'Cliente';
            }

            $name = DB::connection(self::LEGACY_CONNECTION)
                ->table('users')
                ->where('id', $userId)
                ->value('name');

            $cleanName = trim((string) ($name ?? ''));
            return $cleanName !== '' ? $cleanName : 'Cliente';
        } catch (Throwable) {
            return 'Cliente';
        }
    }

    private function sendNotificationEmail(string $email, string $customerName, string $title, string $message, ?string $eventKey): bool
    {
        try {
            $html = $this->buildGlobalEmailHtml($customerName, $title, $message, $eventKey);

            Mail::html($html, function ($mail) use ($email, $customerName, $title): void {
                $mail->to($email, $customerName)
                    ->subject($title);
            });

            return true;
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar correo de notificacion.', [
                'email' => $email,
                'event_key' => $eventKey,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function buildGlobalEmailHtml(string $customerName, string $title, string $message, ?string $eventKey): string
    {
        $safeName = e($customerName !== '' ? $customerName : 'Cliente');
        $safeTitle = e($title);
        $safeMessage = e($message);
        $safeDate = e(now('America/Bogota')->format('d/m/Y H:i'));

        $storeUrl = trim((string) config('services.frontend.store_url', 'http://localhost:5173'));
        if ($storeUrl === '') {
            $storeUrl = 'http://localhost:5173';
        }

        $storeUrl = rtrim($storeUrl, '/');
        $ctaPath = match ($eventKey) {
            self::EVENT_PRODUCT => '/tienda',
            self::EVENT_PROMOTION => '/tienda?offers=1',
            self::EVENT_CART_REMINDER => '/carrito',
            default => '/mi-cuenta/notificaciones',
        };

        $ctaText = match ($eventKey) {
            self::EVENT_PRODUCT => 'Ver productos',
            self::EVENT_PROMOTION => 'Ver ofertas',
            self::EVENT_CART_REMINDER => 'Finalizar compra',
            default => 'Ver notificaciones',
        };

        $safeCtaUrl = e($storeUrl . $ctaPath);
        $safeCtaText = e($ctaText);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeTitle}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px 0; background: #f5f7fb; color: #1f2937; }
        .container { max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f7abf; color: #ffffff; padding: 22px 24px; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 24px; }
        .message { margin: 14px 0 18px; font-size: 15px; line-height: 1.55; }
        .cta a { display: inline-block; background: #0f7abf; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; }
        .footer { padding: 14px 24px; border-top: 1px solid #e5edf6; font-size: 12px; color: #64748b; background: #f8fbff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$safeTitle}</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{$safeName}</strong>,</p>
            <p class="message">{$safeMessage}</p>
            <p class="cta">
                <a href="{$safeCtaUrl}">{$safeCtaText}</a>
            </p>
        </div>
        <div class="footer">
            Generado el {$safeDate} (hora Colombia) · Angelow
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function skipResult(string $userId, ?string $eventKey, string $reason): array
    {
        return [
            'user_id' => $userId,
            'event_key' => $eventKey,
            'notification_id' => null,
            'notification_sent' => false,
            'email_sent' => false,
            'skipped' => true,
            'reason' => $reason,
        ];
    }

    private function nullableTrim(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
