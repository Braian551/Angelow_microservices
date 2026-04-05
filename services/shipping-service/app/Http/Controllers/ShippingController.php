<?php

namespace App\Http\Controllers;

use App\Models\LegacyUserAddress;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ShippingController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public function methods(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('shipping_methods')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function rules(): JsonResponse
    {
        return response()->json([
            'data' => DB::table('shipping_price_rules')->where('is_active', true)->orderBy('min_price')->get(),
        ]);
    }

    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subtotal' => ['required', 'numeric'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $rule = DB::table('shipping_price_rules')
            ->where('is_active', true)
            ->where('min_price', '<=', $data['subtotal'])
            ->where(function ($query) use ($data) {
                $query->whereNull('max_price')->orWhere('max_price', '>=', $data['subtotal']);
            })
            ->orderByDesc('min_price')
            ->first();

        return response()->json([
            'subtotal' => $data['subtotal'],
            'city' => $data['city'] ?? null,
            'shipping_cost' => $rule?->shipping_cost ?? 0,
            'rule' => $rule,
        ]);
    }

    /**
     * Lista direcciones activas del usuario para dashboard y checkout.
     */
    public function userAddresses(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json(['data' => []]);
        }

        $addresses = $this->fetchLegacyUserAddresses($candidateUserIds);

        // Fallback temporal a tabla distribuida si en legacy no hay datos.
        if ($addresses->isEmpty()) {
            $addresses = $this->fetchDistributedUserAddresses($candidateUserIds);
        }

        return response()->json([
            'data' => $addresses->values(),
        ]);
    }

    /**
     * Crea una direccion y mantiene regla de "una principal".
     */
    public function createUserAddress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'address_type' => ['required', 'string', 'max:30'],
            'alias' => ['required', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:255'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'building_type' => ['required', 'string', 'max:30'],
            'building_name' => ['nullable', 'string', 'max:120'],
            'apartment_number' => ['nullable', 'string', 'max:40'],
            'delivery_instructions' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $preferredUserId = $this->resolvePreferredUserId(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if ($preferredUserId === null) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        $payload = $this->buildLegacyPayload($data, $preferredUserId);

        $created = DB::connection(self::LEGACY_CONNECTION)->transaction(function () use ($preferredUserId, $payload) {
            $mustBeDefault = (bool) ($payload['is_default'] ?? false);

            if (!$mustBeDefault) {
                $hasAnyAddress = LegacyUserAddress::query()
                    ->where('user_id', $preferredUserId)
                    ->exists();

                if (!$hasAnyAddress) {
                    $payload['is_default'] = true;
                    $mustBeDefault = true;
                }
            }

            if ($mustBeDefault) {
                LegacyUserAddress::query()
                    ->where('user_id', $preferredUserId)
                    ->update(['is_default' => false]);
            }

            return LegacyUserAddress::query()->create($payload);
        });

        return response()->json([
            'message' => 'Dirección creada correctamente.',
            'data' => $this->normalizeAddressRecord($created),
        ], 201);
    }

    /**
     * Actualiza una direccion existente.
     */
    public function updateUserAddress(Request $request, int $addressId): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'address_type' => ['required', 'string', 'max:30'],
            'alias' => ['required', 'string', 'max:80'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:255'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'building_type' => ['required', 'string', 'max:30'],
            'building_name' => ['nullable', 'string', 'max:120'],
            'apartment_number' => ['nullable', 'string', 'max:40'],
            'delivery_instructions' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        $address = $this->findLegacyAddressForUser($addressId, $candidateUserIds);

        if (!$address) {
            return response()->json([
                'message' => 'Dirección no encontrada para este usuario.',
            ], 404);
        }

        $payload = $this->buildLegacyPayload($data, (string) $address->user_id);

        $updated = DB::connection(self::LEGACY_CONNECTION)->transaction(function () use ($address, $payload) {
            $mustBeDefault = (bool) ($payload['is_default'] ?? false);

            if ($mustBeDefault) {
                LegacyUserAddress::query()
                    ->where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->fill($payload);
            $address->save();

            if (!$mustBeDefault) {
                $hasDefault = LegacyUserAddress::query()
                    ->where('user_id', $address->user_id)
                    ->where('is_default', true)
                    ->exists();

                if (!$hasDefault) {
                    $address->is_default = true;
                    $address->save();
                }
            }

            return $address->fresh();
        });

        return response()->json([
            'message' => 'Dirección actualizada correctamente.',
            'data' => $this->normalizeAddressRecord($updated),
        ]);
    }

    /**
     * Elimina una direccion del usuario.
     */
    public function deleteUserAddress(Request $request, int $addressId): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        $address = $this->findLegacyAddressForUser($addressId, $candidateUserIds);

        if (!$address) {
            return response()->json([
                'message' => 'Dirección no encontrada para este usuario.',
            ], 404);
        }

        DB::connection(self::LEGACY_CONNECTION)->transaction(function () use ($address) {
            $ownerUserId = (string) $address->user_id;
            $wasDefault = (bool) $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $nextAddress = LegacyUserAddress::query()
                    ->where('user_id', $ownerUserId)
                    ->orderByDesc('created_at')
                    ->first();

                if ($nextAddress) {
                    $nextAddress->is_default = true;
                    $nextAddress->save();
                }
            }
        });

        return response()->json([
            'message' => 'Dirección eliminada correctamente.',
        ]);
    }

    /**
     * Establece una direccion como principal.
     */
    public function setDefaultUserAddress(Request $request, int $addressId): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        $address = $this->findLegacyAddressForUser($addressId, $candidateUserIds);

        if (!$address) {
            return response()->json([
                'message' => 'Dirección no encontrada para este usuario.',
            ], 404);
        }

        DB::connection(self::LEGACY_CONNECTION)->transaction(function () use ($address) {
            LegacyUserAddress::query()
                ->where('user_id', $address->user_id)
                ->update(['is_default' => false]);

            $address->is_default = true;
            $address->save();
        });

        return response()->json([
            'message' => 'Dirección principal actualizada.',
            'data' => $this->normalizeAddressRecord($address->fresh()),
        ]);
    }

    private function fetchDistributedUserAddresses(array $candidateUserIds): Collection
    {
        return UserAddress::query()
            ->whereIn('user_id', $candidateUserIds)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (UserAddress $address) => $this->normalizeAddressRecord($address))
            ->values();
    }

    private function fetchLegacyUserAddresses(array $candidateUserIds): Collection
    {
        try {
            return LegacyUserAddress::query()
                ->whereIn('user_id', $candidateUserIds)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (LegacyUserAddress $address) => $this->normalizeAddressRecord($address))
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    private function findLegacyAddressForUser(int $addressId, array $candidateUserIds): ?LegacyUserAddress
    {
        try {
            return LegacyUserAddress::query()
                ->where('id', $addressId)
                ->whereIn('user_id', $candidateUserIds)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function buildLegacyPayload(array $data, string $userId): array
    {
        return [
            'user_id' => $userId,
            'address_type' => $this->nullableString($data['address_type'] ?? null) ?? 'casa',
            'alias' => $this->nullableString($data['alias'] ?? null) ?? 'Dirección',
            'recipient_name' => $this->nullableString($data['recipient_name'] ?? null) ?? '',
            'recipient_phone' => $this->nullableString($data['recipient_phone'] ?? null) ?? '',
            'address' => $this->nullableString($data['address'] ?? null) ?? '',
            'complement' => $this->nullableString($data['complement'] ?? null),
            'neighborhood' => $this->nullableString($data['neighborhood'] ?? null) ?? '',
            'building_type' => $this->nullableString($data['building_type'] ?? null) ?? 'casa',
            'building_name' => $this->nullableString($data['building_name'] ?? null),
            'apartment_number' => $this->nullableString($data['apartment_number'] ?? null),
            'delivery_instructions' => $this->nullableString($data['delivery_instructions'] ?? null),
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => true,
        ];
    }

    private function normalizeAddressRecord(mixed $record): array
    {
        $row = $this->toRecordArray($record);

        $address = $this->nullableString($row['address'] ?? null)
            ?? $this->nullableString($row['address_line_1'] ?? null)
            ?? '';

        $complement = $this->nullableString($row['complement'] ?? null)
            ?? $this->nullableString($row['address_line_2'] ?? null);

        $neighborhood = $this->nullableString($row['neighborhood'] ?? null)
            ?? $this->nullableString($row['city'] ?? null)
            ?? '';

        $addressType = $this->nullableString($row['address_type'] ?? null) ?? 'casa';
        $buildingType = $this->nullableString($row['building_type'] ?? null) ?? $addressType;
        $phone = $this->nullableString($row['recipient_phone'] ?? null)
            ?? $this->nullableString($row['phone'] ?? null)
            ?? '';

        $alias = $this->nullableString($row['alias'] ?? null) ?? ucfirst($addressType);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'user_id' => (string) ($row['user_id'] ?? ''),
            'address_type' => $addressType,
            'alias' => $alias,
            'recipient_name' => (string) ($row['recipient_name'] ?? ''),
            'recipient_phone' => $phone,
            'phone' => $phone,
            'address' => $address,
            'address_line_1' => $address,
            'complement' => $complement,
            'address_line_2' => $complement,
            'neighborhood' => $neighborhood,
            'city' => (string) ($row['city'] ?? ''),
            'department' => (string) ($row['department'] ?? ''),
            'postal_code' => (string) ($row['postal_code'] ?? ''),
            'country' => (string) ($row['country'] ?? 'Colombia'),
            'building_type' => $buildingType,
            'building_name' => $this->nullableString($row['building_name'] ?? null),
            'apartment_number' => $this->nullableString($row['apartment_number'] ?? null),
            'delivery_instructions' => $this->nullableString($row['delivery_instructions'] ?? null)
                ?? $this->nullableString($row['notes'] ?? null),
            'is_default' => (bool) ($row['is_default'] ?? false),
            'is_active' => (bool) ($row['is_active'] ?? true),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    private function toRecordArray(mixed $record): array
    {
        if ($record instanceof LegacyUserAddress || $record instanceof UserAddress) {
            return $record->toArray();
        }

        if (is_object($record)) {
            return get_object_vars($record);
        }

        return is_array($record) ? $record : [];
    }

    /**
     * Construye candidatos de user_id usando id directo y resolucion por correo en legacy.
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
