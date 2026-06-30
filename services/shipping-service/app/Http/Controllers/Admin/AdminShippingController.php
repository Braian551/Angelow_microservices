<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Models\ShippingPriceRule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Controlador administrativo de configuración de envíos.
 *
 * Gestiona el CRUD completo de métodos de envío y reglas de precio por rango
 * desde el panel de administración. Implementa el mismo patrón de fallback
 * a legacy que el controlador público (ShippingController) para mantener
 * la compatibilidad durante la migración de datos.
 *
 * Todos los endpoints de este controlador están protegidos por el middleware
 * EnsureAdmin, que verifica token JWT contra auth-service y rol de administrador.
 */
class AdminShippingController extends Controller
{
    // ── Métodos de envío ────────────────────────────────────

    /**
     * Obtiene todos los métodos de envío (activos e inactivos) para el panel admin.
     *
     * Ordena por: activos primero, luego por costo base, luego por nombre.
     * Si no hay datos en la base distribuida, recurre a legacy.
     * Retorna los métodos transformados al formato estándar del admin.
     */
    public function methods(): JsonResponse
    {
        $methods = ShippingMethod::query()
            ->orderByDesc('is_active')
            ->orderBy('base_cost')
            ->orderBy('name')
            ->get()
            ->map(fn (ShippingMethod $method) => $this->transformMethod($method));

        // Fallback a legacy si la tabla distribuida está vacía
        if ($methods->isEmpty()) {
            $methods = collect($this->loadLegacyMethods());
        }

        return response()->json(['success' => true, 'data' => $methods]);
    }

    /**
     * Crea un nuevo método de envío.
     *
     * Construye el payload validado y persisté en la base distribuida (shipping-db).
     * Retorna 201 con el ID del nuevo registro.
     */
    public function storeMethod(Request $request): JsonResponse
    {
        $method = ShippingMethod::query()->create($this->buildMethodPayload($request, false));

        return response()->json(['success' => true, 'message' => 'Método creado.', 'id' => $method->id], 201);
    }

    /**
     * Actualiza un método de envío existente.
     *
     * Usa actualización parcial (sometimes en validación) para permitir
     * enviar solo los campos modificados desde el frontend admin.
     */
    public function updateMethod(Request $request, int $id): JsonResponse
    {
        $method = ShippingMethod::query()->find($id);

        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Método no encontrado.'], 404);
        }

        $method->fill($this->buildMethodPayload($request, true));
        $method->save();

        return response()->json(['success' => true, 'message' => 'Método actualizado.']);
    }

    /**
     * Elimina un método de envío por ID.
     *
     * Borrado físico (DELETE) de la base distribuida.
     */
    public function destroyMethod(int $id): JsonResponse
    {
        $deleted = ShippingMethod::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Método no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Método eliminado.']);
    }

    // ── Reglas de precio ────────────────────────────────────

    /**
     * Obtiene todas las reglas de precio por rango para el panel admin.
     *
     * Ordenadas por precio mínimo ascendente.
     * Fallback a legacy si shipping-db está vacía.
     */
    public function rules(): JsonResponse
    {
        $rules = ShippingPriceRule::query()
            ->orderBy('min_price')
            ->get()
            ->map(fn (ShippingPriceRule $rule) => $this->transformRule($rule));

        // Fallback a legacy si no hay reglas en la base distribuida
        if ($rules->isEmpty()) {
            $rules = collect($this->loadLegacyRules());
        }

        return response()->json(['success' => true, 'data' => $rules]);
    }

    /**
     * Carga métodos de envío desde la base legacy (fallback para el panel admin).
     *
     * Se invoca cuando la tabla shipping_methods de shipping-db está vacía.
     * Transforma los registros legacy al mismo formato que usa el admin
     * para mantener consistencia en la UI de configuración.
     */
    private function loadLegacyMethods(): array
    {
        if (!$this->legacyTableExists('shipping_methods')) {
            return [];
        }

        try {
            return DB::connection('legacy_mysql')
                ->table('shipping_methods')
                ->select(
                    'id',
                    'name',
                    'description',
                    'base_cost',
                    'delivery_time',
                    'estimated_days_min',
                    'estimated_days_max',
                    'free_shipping_minimum',
                    'icon',
                    'city',
                    'is_active',
                    'created_at',
                    'updated_at',
                )
                ->orderByDesc('is_active')
                ->orderBy('base_cost')
                ->orderBy('name')
                ->get()
                ->map(fn (object $row) => [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->name ?? ''),
                    'description' => $row->description,
                    'base_cost' => (float) ($row->base_cost ?? 0),
                    'delivery_time' => $row->delivery_time,
                    'estimated_days_min' => $row->estimated_days_min !== null ? (int) $row->estimated_days_min : null,
                    'estimated_days_max' => $row->estimated_days_max !== null ? (int) $row->estimated_days_max : null,
                    'estimated_days' => $row->estimated_days_max !== null
                        ? (int) $row->estimated_days_max
                        : ($row->estimated_days_min !== null ? (int) $row->estimated_days_min : null),
                    'free_shipping_minimum' => $row->free_shipping_minimum !== null ? (float) $row->free_shipping_minimum : null,
                    'icon' => $row->icon,
                    'city' => $row->city,
                    'is_active' => (bool) ($row->is_active ?? false),
                    'active' => (bool) ($row->is_active ?? false),
                    'created_at' => $this->toIsoString($row->created_at ?? null),
                    'updated_at' => $this->toIsoString($row->updated_at ?? null),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Carga reglas de precio desde la base legacy (fallback para el panel admin).
     *
     * Transforma las reglas legacy al formato estándar del admin.
     */
    private function loadLegacyRules(): array
    {
        if (!$this->legacyTableExists('shipping_price_rules')) {
            return [];
        }

        try {
            return DB::connection('legacy_mysql')
                ->table('shipping_price_rules')
                ->select('id', 'min_price', 'max_price', 'shipping_cost', 'is_active', 'created_at', 'updated_at')
                ->orderBy('min_price')
                ->get()
                ->map(fn (object $row) => [
                    'id' => (int) $row->id,
                    'min_price' => (float) ($row->min_price ?? 0),
                    'max_price' => $row->max_price !== null ? (float) $row->max_price : null,
                    'shipping_cost' => (float) ($row->shipping_cost ?? 0),
                    'is_active' => (bool) ($row->is_active ?? false),
                    'active' => (bool) ($row->is_active ?? false),
                    'created_at' => $this->toIsoString($row->created_at ?? null),
                    'updated_at' => $this->toIsoString($row->updated_at ?? null),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Verifica si una tabla existe en la base legacy.
     *
     * Usada por loadLegacyMethods y loadLegacyRules antes de consultar
     * para evitar errores de esquema.
     */
    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection('legacy_mysql')->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Convierte un valor de fecha a string ISO 8601 de forma segura.
     *
     * Retorna null si el valor está vacío o no puede parsearse.
     * Útil para normalizar fechas legacy al formato estándar ISO.
     */
    private function toIsoString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Crea una nueva regla de precio por rango.
     */
    public function storeRule(Request $request): JsonResponse
    {
        $rule = ShippingPriceRule::query()->create($this->buildRulePayload($request, false));

        return response()->json(['success' => true, 'message' => 'Regla creada.', 'id' => $rule->id], 201);
    }

    /**
     * Actualiza una regla de precio existente con validación parcial.
     */
    public function updateRule(Request $request, int $id): JsonResponse
    {
        $rule = ShippingPriceRule::query()->find($id);

        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        $rule->fill($this->buildRulePayload($request, true));
        $rule->save();

        return response()->json(['success' => true, 'message' => 'Regla actualizada.']);
    }

    /**
     * Elimina una regla de precio por ID.
     */
    public function destroyRule(int $id): JsonResponse
    {
        $deleted = ShippingPriceRule::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
    }

    /**
     * Construye el payload validado para crear/actualizar un método de envío.
     *
     * Soporta campos duplicados (is_active/active) para compatibilidad
     * con diferentes versiones del frontend admin.
     *
     * @param bool $partial Si es true, usa validación 'sometimes' (actualización parcial)
     */
    private function buildMethodPayload(Request $request, bool $partial): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'base_cost' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'delivery_time' => ['nullable', 'string', 'max:80'],
            'estimated_days_min' => ['nullable', 'integer', 'min:1'],
            'estimated_days_max' => ['nullable', 'integer', 'min:1'],
            'free_shipping_minimum' => ['nullable', 'numeric', 'min:0'],
            'icon' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];

        $data = $request->validate($rules);
        $payload = [];

        // Construye el payload solo con los campos presentes (soporta PATCH parcial)
        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $this->nullableTrim($data['description'] ?? null);
        }

        if (array_key_exists('base_cost', $data)) {
            $payload['base_cost'] = (float) $data['base_cost'];
        }

        if (array_key_exists('delivery_time', $data)) {
            $payload['delivery_time'] = $this->nullableTrim($data['delivery_time'] ?? null);
        }

        if (array_key_exists('estimated_days_min', $data)) {
            $payload['estimated_days_min'] = $data['estimated_days_min'] ?: null;
        }

        if (array_key_exists('estimated_days_max', $data)) {
            $payload['estimated_days_max'] = $data['estimated_days_max'] ?: null;
        }

        if (array_key_exists('free_shipping_minimum', $data)) {
            $payload['free_shipping_minimum'] = $data['free_shipping_minimum'] !== null ? (float) $data['free_shipping_minimum'] : null;
        }

        if (array_key_exists('icon', $data)) {
            $payload['icon'] = $this->nullableTrim($data['icon'] ?? null);
        }

        if (array_key_exists('city', $data)) {
            $payload['city'] = $this->nullableTrim($data['city'] ?? null);
        }

        // Acepta tanto 'is_active' como 'active' para compatibilidad con distintos frontends
        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        return $payload;
    }

    /**
     * Construye el payload validado para crear/actualizar una regla de precio.
     *
     * Soporta actualización parcial y el campo duplicado is_active/active.
     */
    private function buildRulePayload(Request $request, bool $partial): array
    {
        $rules = [
            'min_price' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];

        $data = $request->validate($rules);
        $payload = [];

        if (array_key_exists('min_price', $data)) {
            $payload['min_price'] = (float) $data['min_price'];
        }

        if (array_key_exists('max_price', $data)) {
            $payload['max_price'] = $data['max_price'] !== null ? (float) $data['max_price'] : null;
        }

        if (array_key_exists('shipping_cost', $data)) {
            $payload['shipping_cost'] = (float) $data['shipping_cost'];
        }

        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        return $payload;
    }

    /**
     * Transforma un modelo ShippingMethod al formato de respuesta del admin.
     *
     * Convierte tipos, calcula estimated_days consolidado y
     * normaliza fechas a ISO 8601.
     */
    private function transformMethod(ShippingMethod $method): array
    {
        return [
            'id' => $method->id,
            'name' => $method->name,
            'description' => $method->description,
            'base_cost' => (float) ($method->base_cost ?? 0),
            'delivery_time' => $method->delivery_time,
            'estimated_days_min' => $method->estimated_days_min,
            'estimated_days_max' => $method->estimated_days_max,
            'estimated_days' => $method->estimated_days_max ?: $method->estimated_days_min,
            'free_shipping_minimum' => $method->free_shipping_minimum,
            'icon' => $method->icon,
            'city' => $method->city,
            'is_active' => (bool) $method->is_active,
            'active' => (bool) $method->is_active,
            'created_at' => optional($method->created_at)?->toISOString(),
            'updated_at' => optional($method->updated_at)?->toISOString(),
        ];
    }

    /**
     * Transforma un modelo ShippingPriceRule al formato de respuesta del admin.
     */
    private function transformRule(ShippingPriceRule $rule): array
    {
        return [
            'id' => $rule->id,
            'min_price' => (float) ($rule->min_price ?? 0),
            'max_price' => $rule->max_price !== null ? (float) $rule->max_price : null,
            'shipping_cost' => (float) ($rule->shipping_cost ?? 0),
            'is_active' => (bool) $rule->is_active,
            'active' => (bool) $rule->is_active,
            'created_at' => optional($rule->created_at)?->toISOString(),
            'updated_at' => optional($rule->updated_at)?->toISOString(),
        ];
    }

    /**
     * Limpia un valor eliminando espacios y retorna null si queda vacío.
     */
    private function nullableTrim(mixed $value): ?string
    {
        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }
}
