<?php

namespace App\Http\Controllers;

use App\Models\LegacyUserAddress;
use App\Models\ShippingMethod;
use App\Models\ShippingPriceRule;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ShippingController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public function methods(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $subtotal = (float) ($data['subtotal'] ?? 0);
        $activeRule = $this->findMatchingRule($subtotal);

        $methods = ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ShippingMethod $method) => $this->normalizeMethodRecord($method, $subtotal, $activeRule));

        if ($methods->isEmpty()) {
            $methods = collect($this->loadLegacyMethods($subtotal, $activeRule));
        }

        return response()->json([
            'data' => $methods->values(),
        ]);
    }

    public function rules(): JsonResponse
    {
        $rules = ShippingPriceRule::query()
            ->where('is_active', true)
            ->orderBy('min_price')
            ->get()
            ->map(fn (ShippingPriceRule $rule) => $this->normalizeRuleRecord($rule));

        if ($rules->isEmpty()) {
            $rules = collect($this->loadLegacyRules())
                ->sortBy('min_price')
                ->values();
        }

        return response()->json([
            'data' => $rules,
        ]);
    }

    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subtotal' => ['required', 'numeric'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $subtotal = (float) $data['subtotal'];
        $rule = $this->findMatchingRule($subtotal);
        $rangeRuleAdditionalCost = (float) ($rule['shipping_cost'] ?? 0);

        return response()->json([
            'subtotal' => $subtotal,
            'city' => $data['city'] ?? null,
            'shipping_cost' => $rangeRuleAdditionalCost,
            'range_rule_additional_cost' => $rangeRuleAdditionalCost,
            'range_rule_label' => $rule['range_label'] ?? null,
            'rule' => $rule,
        ]);
    }

    /**
     * Busca una regla activa por subtotal y usa fallback legacy cuando shipping-db no tiene datos.
     */
    private function findMatchingRule(float $subtotal): ?array
    {
        $rule = ShippingPriceRule::query()
            ->where('is_active', true)
            ->where('min_price', '<=', $subtotal)
            ->where(function ($query) use ($subtotal) {
                $query->whereNull('max_price')->orWhere('max_price', '>=', $subtotal);
            })
            ->orderByDesc('min_price')
            ->first();

        if ($rule) {
            return $this->normalizeRuleRecord($rule);
        }

        $hasDistributedRules = ShippingPriceRule::query()->where('is_active', true)->exists();
        if ($hasDistributedRules || !$this->legacyTableExists('shipping_price_rules')) {
            return null;
        }

        try {
            $legacyRule = DB::connection(self::LEGACY_CONNECTION)
                ->table('shipping_price_rules')
                ->where('is_active', true)
                ->where('min_price', '<=', $subtotal)
                ->where(function ($query) use ($subtotal) {
                    $query->whereNull('max_price')->orWhere('max_price', '>=', $subtotal);
                })
                ->orderByDesc('min_price')
                ->first();

            return $legacyRule ? $this->normalizeRuleRecord($legacyRule) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Fallback legacy para métodos cuando la base distribuida aún no tiene filas.
     */
    private function loadLegacyMethods(float $subtotal, ?array $activeRule): array
    {
        if (!$this->legacyTableExists('shipping_methods')) {
            return [];
        }

        try {
            return DB::connection(self::LEGACY_CONNECTION)
                ->table('shipping_methods')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (object $row) => $this->normalizeMethodRecord($row, $subtotal, $activeRule))
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Fallback legacy para reglas por precio cuando shipping-db aún está vacío.
     */
    private function loadLegacyRules(): array
    {
        if (!$this->legacyTableExists('shipping_price_rules')) {
            return [];
        }

        try {
            return DB::connection(self::LEGACY_CONNECTION)
                ->table('shipping_price_rules')
                ->where('is_active', true)
                ->orderBy('min_price')
                ->get()
                ->map(fn (object $row) => $this->normalizeRuleRecord($row))
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function normalizeMethodRecord(mixed $record, float $subtotal, ?array $activeRule): array
    {
        $row = $this->toRecordArray($record);
        $baseCost = (float) ($row['base_cost'] ?? 0);
        $hasFreeShippingMinimum = array_key_exists('free_shipping_minimum', $row) && $row['free_shipping_minimum'] !== null;
        $freeShippingMinimum = $hasFreeShippingMinimum
            ? (float) $row['free_shipping_minimum']
            : null;

        $resolvedPricing = $this->resolveMethodShippingCost(
            $baseCost,
            $freeShippingMinimum,
            $subtotal,
            $activeRule,
        );

        $hasEstimatedDaysMin = array_key_exists('estimated_days_min', $row) && $row['estimated_days_min'] !== null;
        $estimatedDaysMin = $hasEstimatedDaysMin
            ? (int) $row['estimated_days_min']
            : null;
        $hasEstimatedDaysMax = array_key_exists('estimated_days_max', $row) && $row['estimated_days_max'] !== null;
        $estimatedDaysMax = $hasEstimatedDaysMax
            ? (int) $row['estimated_days_max']
            : null;

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'description' => $this->nullableString($row['description'] ?? null),
            'base_cost' => $baseCost,
            'delivery_time' => $this->nullableString($row['delivery_time'] ?? null),
            'estimated_days_min' => $estimatedDaysMin,
            'estimated_days_max' => $estimatedDaysMax,
            'estimated_days' => $estimatedDaysMax ?? $estimatedDaysMin,
            'free_shipping_minimum' => $freeShippingMinimum,
            'icon' => $this->nullableString($row['icon'] ?? null),
            'city' => $this->nullableString($row['city'] ?? null),
            'is_active' => (bool) ($row['is_active'] ?? false),
            'active' => (bool) ($row['is_active'] ?? false),
            'method_cost' => $resolvedPricing['method_cost'],
            'range_rule_additional_cost' => $resolvedPricing['range_rule_additional_cost'],
            'range_rule_applied' => $resolvedPricing['range_rule_applied'],
            'range_rule_label' => $resolvedPricing['range_rule_label'],
            'range_rule_min_price' => $resolvedPricing['range_rule_min_price'],
            'range_rule_max_price' => $resolvedPricing['range_rule_max_price'],
            'applied_cost' => $resolvedPricing['applied_cost'],
            'pricing_source' => $resolvedPricing['pricing_source'],
            'rule_id' => $resolvedPricing['rule_id'],
            'rule_shipping_cost' => $resolvedPricing['rule_shipping_cost'],
        ];
    }

    private function normalizeRuleRecord(mixed $record): array
    {
        $row = $this->toRecordArray($record);
        $hasMaxPrice = array_key_exists('max_price', $row) && $row['max_price'] !== null;
        $maxPrice = $hasMaxPrice ? (float) $row['max_price'] : null;

        $normalizedRule = [
            'id' => (int) ($row['id'] ?? 0),
            'min_price' => (float) ($row['min_price'] ?? 0),
            'max_price' => $maxPrice,
            'shipping_cost' => (float) ($row['shipping_cost'] ?? 0),
            'is_active' => (bool) ($row['is_active'] ?? false),
            'active' => (bool) ($row['is_active'] ?? false),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];

        $normalizedRule['range_label'] = $this->formatPriceRuleRangeLabel($normalizedRule);

        return $normalizedRule;
    }

    private function resolveMethodShippingCost(float $baseCost, ?float $freeShippingMinimum, float $subtotal, ?array $activeRule): array
    {
        $hasFreeShippingThreshold = $freeShippingMinimum !== null && $freeShippingMinimum > 0;
        $resolvedMethodCost = $baseCost;
        $methodSource = 'base_cost';

        if ($hasFreeShippingThreshold && $subtotal >= $freeShippingMinimum) {
            $resolvedMethodCost = 0.0;
            $methodSource = 'free_shipping_minimum';
        }

        $rangeRuleAdditionalCost = $activeRule !== null
            ? max(0.0, (float) ($activeRule['shipping_cost'] ?? 0))
            : 0.0;

        $hasRangeRule = $activeRule !== null;
        $hasRangeRuleAdditional = $hasRangeRule && $rangeRuleAdditionalCost > 0;

        $resolvedTotalCost = max(0.0, $resolvedMethodCost + $rangeRuleAdditionalCost);
        $pricingSource = $methodSource;

        if ($hasRangeRuleAdditional) {
            $pricingSource = $methodSource === 'free_shipping_minimum'
                ? 'free_shipping_plus_price_rule'
                : 'base_plus_price_rule';
        }

        return [
            'method_cost' => $resolvedMethodCost,
            'range_rule_additional_cost' => $rangeRuleAdditionalCost,
            'range_rule_applied' => $hasRangeRule,
            'range_rule_label' => $hasRangeRule ? $this->formatPriceRuleRangeLabel($activeRule) : null,
            'range_rule_min_price' => $hasRangeRule ? (float) ($activeRule['min_price'] ?? 0) : null,
            'range_rule_max_price' => $hasRangeRule
                ? (($activeRule['max_price'] ?? null) !== null ? (float) $activeRule['max_price'] : null)
                : null,
            'applied_cost' => $resolvedTotalCost,
            'pricing_source' => $pricingSource,
            'rule_id' => $activeRule['id'] ?? null,
            'rule_shipping_cost' => $hasRangeRule ? $rangeRuleAdditionalCost : null,
        ];
    }

    private function formatPriceRuleRangeLabel(array $rule): string
    {
        $minPriceLabel = $this->formatCopCurrency((float) ($rule['min_price'] ?? 0));
        $maxPrice = $rule['max_price'] ?? null;

        if ($maxPrice === null || $maxPrice === '') {
            return sprintf('Desde %s', $minPriceLabel);
        }

        return sprintf('%s a %s', $minPriceLabel, $this->formatCopCurrency((float) $maxPrice));
    }

    private function formatCopCurrency(float $value): string
    {
        return '$' . number_format((int) round($value), 0, ',', '.');
    }

    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection(self::LEGACY_CONNECTION)->hasTable($table);
        } catch (Throwable) {
            return false;
        }
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

        try {
            $created = $this->createAddressInSource('legacy', $preferredUserId, $payload);
        } catch (Throwable $legacyError) {
            $this->logLegacyFallbackIssue('create', $legacyError);

            try {
                $created = $this->createAddressInSource('distributed', $preferredUserId, $payload);
            } catch (Throwable $distributedError) {
                report($distributedError);

                return response()->json([
                    'message' => 'No fue posible guardar la dirección. Intenta nuevamente en unos minutos.',
                ], 500);
            }
        }

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

        $legacyAddress = $this->findLegacyAddressForUser($addressId, $candidateUserIds);

        if ($legacyAddress) {
            $payload = $this->buildLegacyPayload($data, (string) $legacyAddress->user_id);

            try {
                $updated = $this->updateAddressInSource('legacy', $legacyAddress, $payload);

                return response()->json([
                    'message' => 'Dirección actualizada correctamente.',
                    'data' => $this->normalizeAddressRecord($updated),
                ]);
            } catch (Throwable $legacyError) {
                $this->logLegacyFallbackIssue('update', $legacyError);
            }
        }

        $distributedAddress = $this->findDistributedAddressForUser($addressId, $candidateUserIds);

        if ($distributedAddress) {
            $payload = $this->buildLegacyPayload($data, (string) $distributedAddress->user_id);

            try {
                $updated = $this->updateAddressInSource('distributed', $distributedAddress, $payload);

                return response()->json([
                    'message' => 'Dirección actualizada correctamente.',
                    'data' => $this->normalizeAddressRecord($updated),
                ]);
            } catch (Throwable $distributedError) {
                report($distributedError);

                return response()->json([
                    'message' => 'No fue posible actualizar la dirección. Intenta nuevamente en unos minutos.',
                ], 500);
            }
        }

        if ($legacyAddress) {
            return response()->json([
                'message' => 'No fue posible actualizar la dirección. Intenta nuevamente en unos minutos.',
            ], 500);
        }

        return response()->json([
            'message' => 'Dirección no encontrada para este usuario.',
        ], 404);
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

        $legacyAddress = $this->findLegacyAddressForUser($addressId, $candidateUserIds);

        if ($legacyAddress) {
            try {
                $this->deleteAddressInSource('legacy', $legacyAddress);

                return response()->json([
                    'message' => 'Dirección eliminada correctamente.',
                ]);
            } catch (Throwable $legacyError) {
                $this->logLegacyFallbackIssue('delete', $legacyError);
            }
        }

        $distributedAddress = $this->findDistributedAddressForUser($addressId, $candidateUserIds);

        if ($distributedAddress) {
            try {
                $this->deleteAddressInSource('distributed', $distributedAddress);

                return response()->json([
                    'message' => 'Dirección eliminada correctamente.',
                ]);
            } catch (Throwable $distributedError) {
                report($distributedError);

                return response()->json([
                    'message' => 'No fue posible eliminar la dirección. Intenta nuevamente en unos minutos.',
                ], 500);
            }
        }

        if ($legacyAddress) {
            return response()->json([
                'message' => 'No fue posible eliminar la dirección. Intenta nuevamente en unos minutos.',
            ], 500);
        }

        return response()->json([
            'message' => 'Dirección no encontrada para este usuario.',
        ], 404);
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

        $legacyAddress = $this->findLegacyAddressForUser($addressId, $candidateUserIds);

        if ($legacyAddress) {
            try {
                $updated = $this->setDefaultAddressInSource('legacy', $legacyAddress);

                return response()->json([
                    'message' => 'Dirección principal actualizada.',
                    'data' => $this->normalizeAddressRecord($updated),
                ]);
            } catch (Throwable $legacyError) {
                $this->logLegacyFallbackIssue('set_default', $legacyError);
            }
        }

        $distributedAddress = $this->findDistributedAddressForUser($addressId, $candidateUserIds);

        if ($distributedAddress) {
            try {
                $updated = $this->setDefaultAddressInSource('distributed', $distributedAddress);

                return response()->json([
                    'message' => 'Dirección principal actualizada.',
                    'data' => $this->normalizeAddressRecord($updated),
                ]);
            } catch (Throwable $distributedError) {
                report($distributedError);

                return response()->json([
                    'message' => 'No fue posible actualizar la dirección principal. Intenta nuevamente en unos minutos.',
                ], 500);
            }
        }

        if ($legacyAddress) {
            return response()->json([
                'message' => 'No fue posible actualizar la dirección principal. Intenta nuevamente en unos minutos.',
            ], 500);
        }

        return response()->json([
            'message' => 'Dirección no encontrada para este usuario.',
        ], 404);
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

    private function findDistributedAddressForUser(int $addressId, array $candidateUserIds): ?UserAddress
    {
        try {
            return UserAddress::query()
                ->where('id', $addressId)
                ->whereIn('user_id', $candidateUserIds)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Ejecuta escritura en legacy o distribuido manteniendo una sola dirección principal.
     */
    private function createAddressInSource(string $source, string $userId, array $payload): mixed
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        return DB::connection((string) $connection)->transaction(function () use ($modelClass, $userId, $payload) {
            $mustBeDefault = (bool) ($payload['is_default'] ?? false);

            if (!$mustBeDefault) {
                $hasAnyAddress = $modelClass::query()
                    ->where('user_id', $userId)
                    ->exists();

                if (!$hasAnyAddress) {
                    $payload['is_default'] = true;
                    $mustBeDefault = true;
                }
            }

            if ($mustBeDefault) {
                $modelClass::query()
                    ->where('user_id', $userId)
                    ->update(['is_default' => false]);
            }

            return $modelClass::query()->create($payload);
        });
    }

    private function updateAddressInSource(string $source, mixed $address, array $payload): mixed
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        return DB::connection((string) $connection)->transaction(function () use ($modelClass, $address, $payload) {
            $mustBeDefault = (bool) ($payload['is_default'] ?? false);

            if ($mustBeDefault) {
                $modelClass::query()
                    ->where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->fill($payload);
            $address->save();

            if (!$mustBeDefault) {
                $hasDefault = $modelClass::query()
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
    }

    private function deleteAddressInSource(string $source, mixed $address): void
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        DB::connection((string) $connection)->transaction(function () use ($modelClass, $address) {
            $ownerUserId = (string) $address->user_id;
            $wasDefault = (bool) $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $nextAddress = $modelClass::query()
                    ->where('user_id', $ownerUserId)
                    ->orderByDesc('created_at')
                    ->first();

                if ($nextAddress) {
                    $nextAddress->is_default = true;
                    $nextAddress->save();
                }
            }
        });
    }

    private function setDefaultAddressInSource(string $source, mixed $address): mixed
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        return DB::connection((string) $connection)->transaction(function () use ($modelClass, $address) {
            $modelClass::query()
                ->where('user_id', $address->user_id)
                ->update(['is_default' => false]);

            $address->is_default = true;
            $address->save();

            return $address->fresh();
        });
    }

    private function logLegacyFallbackIssue(string $operation, Throwable $error): void
    {
        logger()->warning('shipping-service: fallback a shipping-db por indisponibilidad de legacy_mysql.', [
            'operation' => $operation,
            'error' => $error->getMessage(),
        ]);
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

        if (is_object($record) && method_exists($record, 'toArray')) {
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
