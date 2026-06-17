<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\NotificationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Controlador de preferencias de notificación del usuario.
 * Permite consultar y actualizar qué tipos de notificaciones
 * (productos, promociones, recordatorios de carrito) quiere recibir
 * un usuario, y por qué canales (email, push).
 */
class NotificationPreferenceController extends Controller
{
    /** Conexión legacy donde vive la tabla de preferencias. */
    private const LEGACY_CONNECTION = 'legacy_mysql';

    /**
     * Muestra las preferencias actuales del usuario.
     * Si no existen, retorna los valores por defecto (todo habilitado).
     */
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

        // Asegura que existan los tipos base antes de construir la respuesta.
        $this->ensureDefaultNotificationTypes();

        return response()->json([
            'data' => $this->buildPreferencesPayload($userId),
        ]);
    }

    /**
     * Actualiza las preferencias de notificación del usuario.
     * Cada tipo (product, promotion, order) se actualiza o crea
     * mediante upsert en la tabla legacy notification_preferences.
     */
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

        $this->ensureDefaultNotificationTypes();

        $typesByName = NotificationType::query()
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->keyBy(function (NotificationType $type): string {
                return Str::lower((string) $type->name);
            });

        // Ejecuta toda la actualización en una transacción sobre legacy.
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

            // Actualiza el flag global de email habilitado para todas las preferencias del usuario.
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

    /**
     * Crea o actualiza una preferencia individual para un tipo concreto.
     */
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

    /**
     * Construye el payload completo de preferencias para un usuario.
     * Si no hay preferencias registradas, retorna los valores por defecto.
     */
    private function buildPreferencesPayload(string $userId): array
    {
        $this->ensureDefaultNotificationTypes();

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

        // Email global: si alguna preferencia tiene email_enabled=false, se considera deshabilitado.
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

    /**
     * Resuelve si un tipo específico tiene push habilitado, con valor por defecto.
     */
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

    /**
     * Resuelve el ID de usuario preferido: primero el user_id directo,
     * luego por resolución de correo en legacy.
     */
    private function resolvePreferredUserId(?string $userId, ?string $userEmail): ?string
    {
        if ($userId !== null && $userId !== '') {
            return $userId;
        }

        return $this->resolveLegacyUserIdByEmail($userEmail);
    }

    /**
     * Busca el ID de un usuario legacy a partir de su correo electrónico.
     */
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

    /**
     * Normaliza un valor a string nullable.
     */
    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }

    /**
     * Garantiza que existan los tipos base usados por preferencias de cliente.
     * Si un tipo existe pero está inactivo o sin descripción, lo reactiva y completa.
     * Si no existe, lo crea con valores predeterminados.
     */
    private function ensureDefaultNotificationTypes(): void
    {
        $definitions = [
            ['name' => 'product', 'description' => 'Nuevos productos disponibles'],
            ['name' => 'promotion', 'description' => 'Promociones y ofertas'],
            ['name' => 'order', 'description' => 'Recordatorios de carrito y avisos operativos'],
        ];

        foreach ($definitions as $definition) {
            $existing = NotificationType::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($definition['name'])])
                ->first();

            if ($existing) {
                if (!$existing->is_active || trim((string) $existing->description) === '') {
                    $existing->is_active = true;
                    $existing->description = $definition['description'];
                    $existing->updated_at = now();
                    $existing->save();
                }

                continue;
            }

            NotificationType::query()->create([
                'name' => $definition['name'],
                'description' => $definition['description'],
                'is_active' => true,
            ]);
        }
    }
}
