<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\NotificationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class NotificationPreferenceController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $userId = $this->resolvePreferredUserId(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if ($userId === null) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        return response()->json([
            'data' => $this->buildPreferencesPayload($userId),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'email_notifications' => ['required', 'boolean'],
            'product_notifications' => ['required', 'boolean'],
            'promotion_notifications' => ['required', 'boolean'],
            'cart_reminders' => ['required', 'boolean'],
        ]);

        $userId = $this->resolvePreferredUserId(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if ($userId === null) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        $typesByName = NotificationType::query()
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->keyBy(function (NotificationType $type): string {
                return Str::lower((string) $type->name);
            });

        DB::connection(self::LEGACY_CONNECTION)->transaction(function () use ($userId, $data, $typesByName): void {
            $emailEnabled = (bool) $data['email_notifications'];

            $this->upsertPreference(
                $userId,
                $typesByName,
                'product',
                $emailEnabled,
                (bool) $data['product_notifications'],
            );

            $this->upsertPreference(
                $userId,
                $typesByName,
                'promotion',
                $emailEnabled,
                (bool) $data['promotion_notifications'],
            );

            // En legacy, el tipo order se reutiliza para avisos operativos/cart reminder.
            $this->upsertPreference(
                $userId,
                $typesByName,
                'order',
                $emailEnabled,
                (bool) $data['cart_reminders'],
            );

            NotificationPreference::query()
                ->where('user_id', $userId)
                ->update([
                    'email_enabled' => $emailEnabled,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'message' => 'Preferencias actualizadas correctamente.',
            'data' => $this->buildPreferencesPayload($userId),
        ]);
    }

    private function upsertPreference(
        string $userId,
        $typesByName,
        string $typeName,
        bool $emailEnabled,
        bool $pushEnabled,
    ): void {
        $type = $typesByName->get(Str::lower($typeName));
        if (!$type) {
            return;
        }

        NotificationPreference::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'type_id' => (int) $type->id,
            ],
            [
                'email_enabled' => $emailEnabled,
                'sms_enabled' => false,
                'push_enabled' => $pushEnabled,
                'updated_at' => now(),
            ],
        );
    }

    private function buildPreferencesPayload(string $userId): array
    {
        $typesByName = NotificationType::query()
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->keyBy(function (NotificationType $type): string {
                return Str::lower((string) $type->name);
            });

        $preferences = NotificationPreference::query()
            ->where('user_id', $userId)
            ->get()
            ->keyBy('type_id');

        $emailNotifications = true;

        if ($preferences->isNotEmpty()) {
            $emailNotifications = (bool) $preferences
                ->pluck('email_enabled')
                ->min();
        }

        $productNotifications = $this->resolvePushPreference($preferences, $typesByName, 'product', true);
        $promotionNotifications = $this->resolvePushPreference($preferences, $typesByName, 'promotion', true);
        $cartReminders = $this->resolvePushPreference($preferences, $typesByName, 'order', true);

        return [
            'email_notifications' => $emailNotifications,
            'product_notifications' => $productNotifications,
            'promotion_notifications' => $promotionNotifications,
            'cart_reminders' => $cartReminders,
        ];
    }

    private function resolvePushPreference($preferences, $typesByName, string $typeName, bool $default): bool
    {
        $type = $typesByName->get(Str::lower($typeName));
        if (!$type) {
            return $default;
        }

        $pref = $preferences->get((int) $type->id);
        if (!$pref) {
            return $default;
        }

        return (bool) $pref->push_enabled;
    }

    private function resolvePreferredUserId(?string $userId, ?string $userEmail): ?string
    {
        if ($userId !== null && $userId !== '') {
            return $userId;
        }

        return $this->resolveLegacyUserIdByEmail($userEmail);
    }

    private function resolveLegacyUserIdByEmail(?string $userEmail): ?string
    {
        if ($userEmail === null || $userEmail === '') {
            return null;
        }

        try {
            $resolvedUserId = DB::connection(self::LEGACY_CONNECTION)
                ->table('users')
                ->whereRaw('LOWER(email) = ?', [Str::lower($userEmail)])
                ->value('id');

            if ($resolvedUserId === null || $resolvedUserId === '') {
                return null;
            }

            return (string) $resolvedUserId;
        } catch (Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
