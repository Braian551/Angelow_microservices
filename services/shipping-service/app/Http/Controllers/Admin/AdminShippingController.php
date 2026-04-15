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

class AdminShippingController extends Controller
{
    // ── Metodos de envio ────────────────────────────────────

    public function methods(): JsonResponse
    {
        $methods = ShippingMethod::query()
            ->orderByDesc('is_active')
            ->orderBy('base_cost')
            ->orderBy('name')
            ->get()
            ->map(fn (ShippingMethod $method) => $this->transformMethod($method));

        if ($methods->isEmpty()) {
            $methods = collect($this->loadLegacyMethods());
        }

        return response()->json(['success' => true, 'data' => $methods]);
    }

    public function storeMethod(Request $request): JsonResponse
    {
        $method = ShippingMethod::query()->create($this->buildMethodPayload($request, false));

        return response()->json(['success' => true, 'message' => 'Metodo creado.', 'id' => $method->id], 201);
    }

    public function updateMethod(Request $request, int $id): JsonResponse
    {
        $method = ShippingMethod::query()->find($id);

        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Metodo no encontrado.'], 404);
        }

        $method->fill($this->buildMethodPayload($request, true));
        $method->save();

        return response()->json(['success' => true, 'message' => 'Metodo actualizado.']);
    }

    public function destroyMethod(int $id): JsonResponse
    {
        $deleted = ShippingMethod::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Metodo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Metodo eliminado.']);
    }

    // ── Reglas de precio ────────────────────────────────────

    public function rules(): JsonResponse
    {
        $rules = ShippingPriceRule::query()
            ->orderBy('min_price')
            ->get()
            ->map(fn (ShippingPriceRule $rule) => $this->transformRule($rule));

        if ($rules->isEmpty()) {
            $rules = collect($this->loadLegacyRules());
        }

        return response()->json(['success' => true, 'data' => $rules]);
    }

    /**
     * Fallback legacy para metodos cuando la base distribuida aun no tiene datos.
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
     * Fallback legacy para reglas por precio cuando la base distribuida aun no tiene datos.
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

    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection('legacy_mysql')->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

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

    public function storeRule(Request $request): JsonResponse
    {
        $rule = ShippingPriceRule::query()->create($this->buildRulePayload($request, false));

        return response()->json(['success' => true, 'message' => 'Regla creada.', 'id' => $rule->id], 201);
    }

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

    public function destroyRule(int $id): JsonResponse
    {
        $deleted = ShippingPriceRule::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
    }

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

        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        return $payload;
    }

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

    private function nullableTrim(mixed $value): ?string
    {
        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }
}
