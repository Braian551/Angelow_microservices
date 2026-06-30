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

/**
 * Controlador principal del servicio de envíos.
 *
 * Gestiona la lógica de métodos de envío, reglas de precio por rango,
 * cálculo de costos estimados y el CRUD completo de direcciones de usuario.
 *
 * Durante la migración, implementa un patrón de fallback transparente:
 * primero consulta la base distribuida (shipping-db) y, si no hay datos,
 * recurre a la base legacy (legacy_mysql) para mantener compatibilidad
 * mientras los datos se migran progresivamente.
 */
class ShippingController extends Controller
{
    /** Nombre de la conexión a la base de datos legacy (Angelow PHP original) */
    private const LEGACY_CONNECTION = 'legacy_mysql';

    /**
     * Obtiene los métodos de envío activos, con costo resuelto según el subtotal.
     *
     * Calcula el costo aplicable combinando la tarifa base del método con
     * la regla de precio por rango activa (si existe). Soporta fallback a
     * legacy cuando shipping-db no tiene métodos registrados.
     */
    public function methods(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Subtotal del carrito usado para calcular costo de envío y reglas aplicables
        $subtotal = (float) ($data['subtotal'] ?? 0);

        // Busca la regla de precio por rango activa que coincida con el subtotal
        $activeRule = $this->findMatchingRule($subtotal);

        // Consulta métodos activos desde la base distribuida (shipping-db)
        $methods = ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ShippingMethod $method) => $this->normalizeMethodRecord($method, $subtotal, $activeRule));

        // Si no hay métodos en la base distribuida, intenta fallback a legacy
        if ($methods->isEmpty()) {
            $methods = collect($this->loadLegacyMethods($subtotal, $activeRule));
        }

        return response()->json([
            'data' => $methods->values(),
        ]);
    }

    /**
     * Obtiene las reglas de precio por rango activas.
     *
     * Las reglas definen costos adicionales de envío según el subtotal del carrito
     * (ej: "de $0 a $50.000 → $9.900 de envío"). Con fallback a legacy si es necesario.
     */
    public function rules(): JsonResponse
    {
        $rules = ShippingPriceRule::query()
            ->where('is_active', true)
            ->orderBy('min_price')
            ->get()
            ->map(fn (ShippingPriceRule $rule) => $this->normalizeRuleRecord($rule));

        // Fallback a legacy si no hay reglas en la base distribuida
        if ($rules->isEmpty()) {
            $rules = collect($this->loadLegacyRules())
                ->sortBy('min_price')
                ->values();
        }

        return response()->json([
            'data' => $rules,
        ]);
    }

    /**
     * Calcula el costo estimado de envío para un subtotal dado.
     *
     * Evalúa la regla de precio activa que aplica al subtotal y retorna
     * el costo adicional, la etiqueta descriptiva del rango y los datos
     * completos de la regla para que el frontend pueda mostrarlos.
     */
    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subtotal' => ['required', 'numeric'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $subtotal = (float) $data['subtotal'];

        // Encuentra la regla activa cuyo rango cubra el subtotal
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
     * Busca una regla activa que coincida con el subtotal, con fallback a legacy.
     *
     * La regla se selecciona por rango: min_price <= subtotal <= max_price.
     * Si max_price es null, aplica desde min_price en adelante.
     * Primero busca en shipping-db; si no encuentra y la tabla distribuida
     * está vacía, intenta en legacy_mysql.
     *
     * @param float $subtotal Subtotal del carrito para evaluar el rango.
     * @return array|null Regla normalizada o null si no hay coincidencia.
     */
    private function findMatchingRule(float $subtotal): ?array
    {
        // Busca en la base distribuida una regla activa cuyo rango cubra el subtotal
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

        // Si ya hay reglas en distribuida pero ninguna coincide, no hay fallback
        $hasDistributedRules = ShippingPriceRule::query()->where('is_active', true)->exists();
        if ($hasDistributedRules || !$this->legacyTableExists('shipping_price_rules')) {
            return null;
        }

        // Fallback: intenta encontrar la regla en la base legacy
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
     * Carga métodos de envío desde la base legacy como fallback.
     *
     * Se invoca cuando shipping-db no tiene métodos registrados.
     * Aplica la misma lógica de normalización que los métodos distribuidos
     * para que el frontend reciba una estructura de datos uniforme.
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
     * Carga reglas de precio desde la base legacy como fallback.
     *
     * Se invoca cuando shipping-db no tiene reglas registradas.
     * Normaliza los registros legacy al mismo formato que los distribuidos.
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

    /**
     * Normaliza un registro de método de envío (modelo o fila legacy) a un formato uniforme.
     *
     * Resuelve el costo de envío combinando la tarifa base, el umbral de envío gratis
     * y la regla de precio activa. Tambien normaliza los días estimados de entrega.
     * Este formato unificado permite al frontend consumir datos sin importar su origen.
     *
     * @param mixed $record Registro del método (ShippingMethod, stdClass o array)
     * @param float $subtotal Subtotal del carrito para resolver costo
     * @param array|null $activeRule Regla de precio activa (opcional)
     * @return array Estructura normalizada del método de envío
     */
    private function normalizeMethodRecord(mixed $record, float $subtotal, ?array $activeRule): array
    {
        $row = $this->toRecordArray($record);
        $baseCost = (float) ($row['base_cost'] ?? 0);
        $hasFreeShippingMinimum = array_key_exists('free_shipping_minimum', $row) && $row['free_shipping_minimum'] !== null;
        $freeShippingMinimum = $hasFreeShippingMinimum
            ? (float) $row['free_shipping_minimum']
            : null;

        // Resuelve el precio final considerando tarifa base, envío gratis y regla de rango
        $resolvedPricing = $this->resolveMethodShippingCost(
            $baseCost,
            $freeShippingMinimum,
            $subtotal,
            $activeRule,
        );

        // Normaliza días estimados de entrega (min/max pueden venir de legacy o distribuida)
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

    /**
     * Normaliza un registro de regla de precio (modelo o fila legacy) a formato uniforme.
     *
     * Convierte los campos de precio a float, booleanos y genera la etiqueta
     * de rango legible (ej: "$10.000 a $50.000") para mostrar en frontend.
     */
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

        // Genera etiqueta legible del rango (ej: "Desde $10.000" o "$10.000 a $50.000")
        $normalizedRule['range_label'] = $this->formatPriceRuleRangeLabel($normalizedRule);

        return $normalizedRule;
    }

    /**
     * Resuelve el costo final de envío combinando tarifa base, envío gratis y regla de rango.
     *
     * Lógica de decisión:
     * 1. Si el subtotal supera el umbral de envío gratis, el costo base se anula.
     * 2. Si hay una regla de rango activa, su costo se suma al método.
     * 3. Se identifica la fuente del precio (base, gratis, combinado) para trazabilidad.
     *
     * @param float $baseCost Costo base del método de envío
     * @param float|null $freeShippingMinimum Umbral para envío gratis (null si no aplica)
     * @param float $subtotal Subtotal del carrito
     * @param array|null $activeRule Regla de precio activa (opcional)
     * @return array Datos resueltos de costo con metadatos de origen
     */
    private function resolveMethodShippingCost(float $baseCost, ?float $freeShippingMinimum, float $subtotal, ?array $activeRule): array
    {
        // Evalúa si aplica envío gratis por umbral de subtotal
        $hasFreeShippingThreshold = $freeShippingMinimum !== null && $freeShippingMinimum > 0;
        $resolvedMethodCost = $baseCost;
        $methodSource = 'base_cost';

        if ($hasFreeShippingThreshold && $subtotal >= $freeShippingMinimum) {
            $resolvedMethodCost = 0.0;
            $methodSource = 'free_shipping_minimum';
        }

        // Costo adicional de la regla de rango activa
        $rangeRuleAdditionalCost = $activeRule !== null
            ? max(0.0, (float) ($activeRule['shipping_cost'] ?? 0))
            : 0.0;

        $hasRangeRule = $activeRule !== null;
        $hasRangeRuleAdditional = $hasRangeRule && $rangeRuleAdditionalCost > 0;

        // Costo total = costo del método + costo adicional de regla de rango
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

    /**
     * Genera una etiqueta legible para el rango de una regla de precio.
     *
     * Ejemplos de salida:
     * - "Desde $10.000" (cuando max_price es null)
     * - "$10.000 a $50.000" (cuando ambos límites están definidos)
     */
    private function formatPriceRuleRangeLabel(array $rule): string
    {
        $minPriceLabel = $this->formatCopCurrency((float) ($rule['min_price'] ?? 0));
        $maxPrice = $rule['max_price'] ?? null;

        if ($maxPrice === null || $maxPrice === '') {
            return sprintf('Desde %s', $minPriceLabel);
        }

        return sprintf('%s a %s', $minPriceLabel, $this->formatCopCurrency((float) $maxPrice));
    }

    /**
     * Formatea un valor numérico como moneda colombiana (COP).
     *
     * Ejemplo: 10500 → "$10.500"
     * Usa el formato de números enteros con separador de miles.
     */
    private function formatCopCurrency(float $value): string
    {
        return '$' . number_format((int) round($value), 0, ',', '.');
    }

    /**
     * Verifica si una tabla existe en la conexión legacy.
     *
     * Útil para los fallbacks: antes de consultar legacy, validamos
     * que la tabla exista para evitar errores de conexión o esquema.
     */
    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection(self::LEGACY_CONNECTION)->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Obtiene las direcciones activas de un usuario (dashboard y checkout).
     *
     * El usuario se identifica por user_id, user_email o ambos.
     * Primero consulta en la base legacy (fuente primaria durante migración),
     * y si no encuentra datos, recurre a la tabla distribuida (shipping-db).
     * Retorna las direcciones ordenadas por predeterminada primero y luego por fecha.
     */
    public function userAddresses(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:64'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        // Genera lista de IDs candidatos a partir del user_id directo y/o resolución por email
        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json(['data' => []]);
        }

        // Primero busca en legacy (fuente primaria durante la migración)
        $addresses = $this->fetchLegacyUserAddresses($candidateUserIds);

        // Fallback a tabla distribuida si legacy no tiene datos o no está disponible
        if ($addresses->isEmpty()) {
            $addresses = $this->fetchDistributedUserAddresses($candidateUserIds);
        }

        return response()->json([
            'data' => $addresses->values(),
        ]);
    }

    /**
     * Crea una nueva dirección de envío para el usuario.
     *
     * La escritura se intenta primero en legacy; si falla, se reintenta
     * en la base distribuida. Mantiene la regla de negocio de "una sola
     * dirección principal" por usuario: si la nueva dirección se marca
     * como predeterminada, las demás se desmarcan automáticamente.
     * Si es la primera dirección del usuario, se fuerza como predeterminada.
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

        // Resuelve el user_id preferido (user_id directo > resolución por email legacy)
        $preferredUserId = $this->resolvePreferredUserId(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if ($preferredUserId === null) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        // Construye el payload con el formato legacy (campo a campo)
        $payload = $this->buildLegacyPayload($data, $preferredUserId);

        // Intenta crear en legacy; si falla, fallback a distribuida
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
     * Actualiza una dirección existente del usuario.
     *
     * Busca la dirección primero en legacy; si la encuentra y la actualización
     * falla, intenta en distribuida. Si no está en legacy, busca directamente
     * en distribuida. Mantiene la regla de una sola dirección principal.
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

        // Construye candidatos de user_id desde los datos de la solicitud
        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            return response()->json([
                'message' => 'Debes enviar user_id o user_email válido.',
            ], 422);
        }

        // Intenta actualizar en legacy primero (ubicación primaria de datos)
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

        // Si no estaba en legacy o la actualización falló, busca en distribuida
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

        // Si la dirección existía en legacy pero falló la escritura
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
     * Elimina (borrado lógico) una dirección del usuario.
     *
     * Busca la dirección en legacy primero; si la encuentra, la elimina.
     * Si no, busca en distribuida. Al eliminar una dirección principal,
     * asigna automáticamente como principal a la más reciente del usuario.
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

        // Intenta eliminar en legacy
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

        // Si no estaba en legacy, busca y elimina en distribuida
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
     * Establece una dirección como principal para el usuario.
     *
     * Desmarca cualquier otra dirección principal del mismo usuario
     * y marca la solicitada. Busca en legacy primero; si no está,
     * opera en distribuida.
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

        // Intenta en legacy primero
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

        // Si no está en legacy, busca en distribuida
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

    /**
     * Busca direcciones del usuario en la tabla distribuida (shipping-db).
     *
     * Retorna solo direcciones activas, ordenadas por predeterminada primero
     * y luego por fecha de creación descendente.
     */
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

    /**
     * Busca direcciones del usuario en la base legacy (legacy_mysql).
     *
     * Envuelve en try/catch para manejar desconexión de la base legacy
     * sin interrumpir la respuesta. Retorna colección vacía en caso de error.
     */
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

    /**
     * Encuentra una dirección legacy por ID y candidatos de user_id.
     *
     * Usado en operaciones de actualización, eliminación y cambio de predeterminada.
     */
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

    /**
     * Encuentra una dirección distribuida por ID y candidatos de user_id.
     *
     * Fallback cuando la dirección no existe en legacy.
     */
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
     * Crea una dirección en la fuente especificada (legacy o distribuida) dentro de una transacción.
     *
     * Lógica transaccional:
     * - Si es la primera dirección del usuario, se fuerza como predeterminada.
     * - Si la nueva dirección se marca como predeterminada, se desmarcan las demás.
     * Todo dentro de la misma transacción para mantener consistencia.
     *
     * @param string $source 'legacy' o 'distributed'
     * @param string $userId ID del usuario propietario
     * @param array $payload Datos normalizados de la dirección
     * @return LegacyUserAddress|UserAddress Modelo creado
     */
    private function createAddressInSource(string $source, string $userId, array $payload): mixed
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        return DB::connection((string) $connection)->transaction(function () use ($modelClass, $userId, $payload) {
            $mustBeDefault = (bool) ($payload['is_default'] ?? false);

            // Si el usuario no tiene direcciones, la primera debe ser predeterminada
            if (!$mustBeDefault) {
                $hasAnyAddress = $modelClass::query()
                    ->where('user_id', $userId)
                    ->exists();

                if (!$hasAnyAddress) {
                    $payload['is_default'] = true;
                    $mustBeDefault = true;
                }
            }

            // Si es predeterminada, desmarca las demás del mismo usuario
            if ($mustBeDefault) {
                $modelClass::query()
                    ->where('user_id', $userId)
                    ->update(['is_default' => false]);
            }

            return $modelClass::query()->create($payload);
        });
    }

    /**
     * Actualiza una dirección existente en la fuente especificada, dentro de una transacción.
     *
     * Si la dirección se marca como predeterminada, desmarca las demás.
     * Si se desmarca como predeterminada pero no hay otra principal,
     * esta misma dirección se conserva como predeterminada para evitar
     * usuarios sin dirección principal.
     *
     * @param string $source 'legacy' o 'distributed'
     * @param LegacyUserAddress|UserAddress $address Modelo de la dirección a actualizar
     * @param array $payload Nuevos datos
     * @return LegacyUserAddress|UserAddress Modelo actualizado (fresh)
     */
    private function updateAddressInSource(string $source, mixed $address, array $payload): mixed
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        return DB::connection((string) $connection)->transaction(function () use ($modelClass, $address, $payload) {
            $mustBeDefault = (bool) ($payload['is_default'] ?? false);

            // Si se marca como predeterminada, desmarca las demás
            if ($mustBeDefault) {
                $modelClass::query()
                    ->where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->fill($payload);
            $address->save();

            // Si no se marcó como predeterminada pero no hay ninguna otra,
            // esta se mantiene como principal para evitar huérfanos
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

    /**
     * Elimina una dirección (borrado lógico) dentro de una transacción.
     *
     * Si la dirección eliminada era la predeterminada, asigna la más reciente
     * como nueva predeterminada. Esto asegura que el usuario siempre tenga
     * al menos una dirección principal si le quedan direcciones activas.
     */
    private function deleteAddressInSource(string $source, mixed $address): void
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        DB::connection((string) $connection)->transaction(function () use ($modelClass, $address) {
            $ownerUserId = (string) $address->user_id;
            $wasDefault = (bool) $address->is_default;

            $address->delete();

            // Si la eliminada era la principal, asigna la más reciente como nueva principal
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

    /**
     * Establece una dirección como predeterminada dentro de una transacción.
     *
     * Desmarca todas las demás direcciones del usuario como no predeterminadas,
     * luego marca la solicitada. La operación es atómica.
     */
    private function setDefaultAddressInSource(string $source, mixed $address): mixed
    {
        $modelClass = $source === 'legacy' ? LegacyUserAddress::class : UserAddress::class;
        $connection = $source === 'legacy' ? self::LEGACY_CONNECTION : config('database.default');

        return DB::connection((string) $connection)->transaction(function () use ($modelClass, $address) {
            // Desmarca todas las direcciones del usuario
            $modelClass::query()
                ->where('user_id', $address->user_id)
                ->update(['is_default' => false]);

            // Marca la solicitada como predeterminada
            $address->is_default = true;
            $address->save();

            return $address->fresh();
        });
    }

    /**
     * Registra en el log las incidencias de fallback a shipping-db.
     *
     * Cuando legacy_mysql no responde, se registra una advertencia
     * con la operación afectada y el mensaje de error para monitoreo.
     */
    private function logLegacyFallbackIssue(string $operation, Throwable $error): void
    {
        logger()->warning('shipping-service: fallback a shipping-db por indisponibilidad de legacy_mysql.', [
            'operation' => $operation,
            'error' => $error->getMessage(),
        ]);
    }

    /**
     * Construye el payload de dirección con el formato de la tabla legacy.
     *
     * Normaliza campos opcionales a null cuando están vacíos,
     * establece valores predeterminados para campos obligatorios
     * y asegura que la dirección se cree como activa.
     */
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

    /**
     * Normaliza un registro de dirección (de cualquier origen) a un formato uniforme.
     *
     * Concilia diferencias de nombres de campo entre legacy y distribuida:
     * - 'address' / 'address_line_1' → 'address'
     * - 'complement' / 'address_line_2' → 'complement'
     * - 'neighborhood' / 'city' (como barrio) → 'neighborhood'
     * - 'recipient_phone' / 'phone' → 'phone'
     * - 'delivery_instructions' / 'notes' → 'delivery_instructions'
     *
     * Esto permite al frontend consumir un contrato único sin importar
     * la fuente de datos, y facilita la migración progresiva.
     */
    private function normalizeAddressRecord(mixed $record): array
    {
        $row = $this->toRecordArray($record);

        // Concilia nombres de campo entre esquemas legacy y distribuido
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

    /**
     * Convierte cualquier tipo de registro a array de forma segura.
     *
     * Soporta modelos Eloquent, stdClass, objetos con toArray() y arrays.
     * Útil para normalizar datos que pueden venir de orígenes diferentes
     * (Eloquent models o filas de Query Builder legacy).
     */
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
     * Construye una lista de IDs candidatos para buscar direcciones.
     *
     * Usa el user_id directo si se proporciona y también resuelve
     * el ID legacy a partir del email. Esto permite encontrar direcciones
     * sin importar si el usuario se identificó por ID o por correo.
     * Reutiliza la resolución legacy por email desde ShippingController.
     */
    private function buildCandidateUserIds(?string $userId, ?string $userEmail): array
    {
        $candidateUserIds = [];

        if ($userId !== null && $userId !== '') {
            $candidateUserIds[] = $userId;
        }

        // Resuelve ID legacy a partir del email (para usuarios que solo envían correo)
        $legacyUserId = $this->resolveLegacyUserIdByEmail($userEmail);

        if ($legacyUserId !== null && !in_array($legacyUserId, $candidateUserIds, true)) {
            $candidateUserIds[] = $legacyUserId;
        }

        return $candidateUserIds;
    }

    /**
     * Resuelve el user_id preferido para operaciones de escritura.
     *
     * Prioriza el user_id explícito; si no se proporciona, intenta
     * resolverlo desde el email contra la tabla legacy de usuarios.
     * Útil durante la migración cuando el frontend SPA puede no tener
     * el user_id legacy disponible.
     */
    private function resolvePreferredUserId(?string $userId, ?string $userEmail): ?string
    {
        if ($userId !== null && $userId !== '') {
            return $userId;
        }

        return $this->resolveLegacyUserIdByEmail($userEmail);
    }

    /**
     * Resuelve el ID de usuario legacy a partir de su correo electrónico.
     *
     * Consulta la tabla 'users' de la base legacy usando el email
     * en minúsculas (case insensitive mediante LOWER() en SQL).
     * Retorna null si no encuentra el usuario o si hay error de conexión.
     * Esto es fundamental durante la migración porque el frontend SPA
     * puede conocer el email pero no el ID numérico legacy del usuario.
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
     * Normaliza un valor a string o null si está vacío.
     *
     * Utilidad compartida que evita almacenar cadenas vacías en la BD.
     * Convierte valores null, cadenas vacías o con solo espacios a null real.
     */
    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
