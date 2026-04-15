<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkDiscountRule;
use App\Models\DiscountCode;
use App\Models\DiscountType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminDiscountController extends Controller
{
    // ── Codigos de descuento ────────────────────────────────

    public function codes(): JsonResponse
    {
        $codes = DiscountCode::query()
            ->with('type')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DiscountCode $code) => $this->transformCode($code));

        if ($codes->isEmpty()) {
            $codes = collect($this->loadLegacyCodes());
        }

        return response()->json(['success' => true, 'data' => $codes]);
    }

    public function storeCode(Request $request): JsonResponse
    {
        $admin = $request->input('_admin_user', []);
        $payload = $this->buildCodePayload($request, false);
        $payload['created_by'] = $admin['id'] ?? null;
        $payload['used_count'] = 0;

        $code = DiscountCode::query()->create($payload);

        return response()->json(['success' => true, 'message' => 'Codigo creado.', 'id' => $code->id], 201);
    }

    public function updateCode(Request $request, int $id): JsonResponse
    {
        $code = DiscountCode::query()->find($id);

        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        $code->fill($this->buildCodePayload($request, true));
        $code->save();

        return response()->json(['success' => true, 'message' => 'Codigo actualizado.']);
    }

    public function destroyCode(int $id): JsonResponse
    {
        $deleted = DiscountCode::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Codigo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Codigo eliminado.']);
    }

    // ── Descuentos por volumen ──────────────────────────────

    public function bulkDiscounts(): JsonResponse
    {
        $rules = BulkDiscountRule::query()
            ->orderBy('min_quantity')
            ->get()
            ->map(fn (BulkDiscountRule $rule) => $this->transformBulkDiscount($rule));

        if ($rules->isEmpty()) {
            $rules = collect($this->loadLegacyBulkDiscounts());
        }

        return response()->json(['success' => true, 'data' => $rules]);
    }

    /**
     * Fallback legacy para codigos cuando el microservicio aun no tiene datos.
     */
    private function loadLegacyCodes(): array
    {
        if (!$this->legacyTableExists('discount_codes')) {
            return [];
        }

        try {
            return DB::connection('legacy_mysql')
                ->table('discount_codes as dc')
                ->leftJoin('discount_types as dt', 'dc.discount_type_id', '=', 'dt.id')
                ->select(
                    'dc.id',
                    'dc.code',
                    'dc.discount_type_id',
                    'dc.discount_value',
                    'dc.max_uses',
                    'dc.used_count',
                    'dc.start_date',
                    'dc.end_date',
                    'dc.is_active',
                    'dc.is_single_use',
                    'dc.created_at',
                    'dc.updated_at',
                    'dt.name as discount_type_name',
                )
                ->orderByDesc('dc.created_at')
                ->get()
                ->map(fn (object $row) => $this->transformLegacyCode($row))
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Fallback legacy para reglas por cantidad cuando el microservicio aun no tiene datos.
     */
    private function loadLegacyBulkDiscounts(): array
    {
        if (!$this->legacyTableExists('bulk_discount_rules')) {
            return [];
        }

        try {
            return DB::connection('legacy_mysql')
                ->table('bulk_discount_rules')
                ->select('id', 'min_quantity', 'max_quantity', 'discount_percentage', 'is_active', 'created_at', 'updated_at')
                ->orderBy('min_quantity')
                ->get()
                ->map(fn (object $row) => [
                    'id' => (int) $row->id,
                    'min_quantity' => (int) ($row->min_quantity ?? 0),
                    'max_quantity' => $row->max_quantity !== null ? (int) $row->max_quantity : null,
                    'discount_percentage' => (float) ($row->discount_percentage ?? 0),
                    'discount_percent' => (float) ($row->discount_percentage ?? 0),
                    'active' => (bool) ($row->is_active ?? false),
                    'is_active' => (bool) ($row->is_active ?? false),
                    'created_at' => $this->toIsoString($row->created_at ?? null),
                    'updated_at' => $this->toIsoString($row->updated_at ?? null),
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function transformLegacyCode(object $row): array
    {
        $typeName = Str::lower((string) ($row->discount_type_name ?? ''));
        $type = str_contains($typeName, 'fixed') || str_contains($typeName, 'fijo') || str_contains($typeName, 'monto')
            ? 'fixed'
            : 'percent';

        return [
            'id' => (int) $row->id,
            'code' => (string) ($row->code ?? ''),
            'type' => $type,
            'type_label' => $type === 'fixed' ? 'Monto fijo' : 'Porcentaje',
            'discount_type_id' => $row->discount_type_id ? (int) $row->discount_type_id : null,
            'discount_type_name' => $row->discount_type_name,
            'value' => (float) ($row->discount_value ?? 0),
            'discount_value' => (float) ($row->discount_value ?? 0),
            'max_uses' => $row->max_uses !== null ? (int) $row->max_uses : null,
            'times_used' => (int) ($row->used_count ?? 0),
            'used_count' => (int) ($row->used_count ?? 0),
            'starts_at' => $this->toIsoString($row->start_date ?? null),
            'start_date' => $this->toIsoString($row->start_date ?? null),
            'expires_at' => $this->toIsoString($row->end_date ?? null),
            'end_date' => $this->toIsoString($row->end_date ?? null),
            'active' => (bool) ($row->is_active ?? false),
            'is_active' => (bool) ($row->is_active ?? false),
            'is_single_use' => (bool) ($row->is_single_use ?? false),
            'created_at' => $this->toIsoString($row->created_at ?? null),
            'updated_at' => $this->toIsoString($row->updated_at ?? null),
        ];
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

    public function storeBulkDiscount(Request $request): JsonResponse
    {
        $rule = BulkDiscountRule::query()->create($this->buildBulkDiscountPayload($request, false));

        return response()->json(['success' => true, 'message' => 'Regla creada.', 'id' => $rule->id], 201);
    }

    public function updateBulkDiscount(Request $request, int $id): JsonResponse
    {
        $rule = BulkDiscountRule::query()->find($id);

        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        $rule->fill($this->buildBulkDiscountPayload($request, true));
        $rule->save();

        return response()->json(['success' => true, 'message' => 'Regla actualizada.']);
    }

    public function destroyBulkDiscount(int $id): JsonResponse
    {
        $deleted = BulkDiscountRule::query()->whereKey($id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Regla no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Regla eliminada.']);
    }

    private function buildCodePayload(Request $request, bool $partial): array
    {
        $data = $request->validate([
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'max:30'],
            'discount_type_id' => ['nullable', 'integer'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'is_single_use' => ['nullable', 'boolean'],
        ]);

        $payload = [];

        if (array_key_exists('code', $data)) {
            $payload['code'] = strtoupper(trim((string) $data['code']));
        }

        if (array_key_exists('discount_type_id', $data) || array_key_exists('type', $data)) {
            $payload['discount_type_id'] = $this->resolveDiscountTypeId(
                $data['discount_type_id'] ?? null,
                $data['type'] ?? null,
            );
        }

        if (array_key_exists('value', $data) || array_key_exists('discount_value', $data)) {
            $payload['discount_value'] = (float) ($data['value'] ?? $data['discount_value'] ?? 0);
        }

        if (array_key_exists('max_uses', $data)) {
            $payload['max_uses'] = $data['max_uses'] ?? null;
        }

        if (array_key_exists('start_date', $data) || array_key_exists('starts_at', $data)) {
            $payload['start_date'] = $data['start_date'] ?? $data['starts_at'] ?? null;
        }

        if (array_key_exists('end_date', $data) || array_key_exists('expires_at', $data)) {
            $payload['end_date'] = $data['end_date'] ?? $data['expires_at'] ?? null;
        }

        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        if (array_key_exists('is_single_use', $data)) {
            $payload['is_single_use'] = (bool) ($data['is_single_use'] ?? false);
        }

        return $payload;
    }

    private function buildBulkDiscountPayload(Request $request, bool $partial): array
    {
        $data = $request->validate([
            'min_quantity' => [$partial ? 'sometimes' : 'required', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        $payload = [];

        if (array_key_exists('min_quantity', $data)) {
            $payload['min_quantity'] = (int) $data['min_quantity'];
        }

        if (array_key_exists('max_quantity', $data)) {
            $payload['max_quantity'] = $data['max_quantity'] ?? null;
        }

        if (array_key_exists('discount_percentage', $data) || array_key_exists('discount_percent', $data)) {
            $payload['discount_percentage'] = (float) ($data['discount_percentage'] ?? $data['discount_percent'] ?? 0);
        }

        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        return $payload;
    }

    private function transformCode(DiscountCode $code): array
    {
        $typeName = strtolower((string) ($code->type?->name ?? ''));
        $type = str_contains($typeName, 'fixed') || str_contains($typeName, 'fijo') ? 'fixed' : 'percent';

        return [
            'id' => $code->id,
            'code' => $code->code,
            'type' => $type,
            'type_label' => $type === 'fixed' ? 'Monto fijo' : 'Porcentaje',
            'discount_type_id' => $code->discount_type_id,
            'discount_type_name' => $code->type?->name,
            'value' => (float) ($code->discount_value ?? 0),
            'discount_value' => (float) ($code->discount_value ?? 0),
            'max_uses' => $code->max_uses,
            'times_used' => (int) ($code->used_count ?? 0),
            'used_count' => (int) ($code->used_count ?? 0),
            'starts_at' => optional($code->start_date)?->toISOString(),
            'start_date' => optional($code->start_date)?->toISOString(),
            'expires_at' => optional($code->end_date)?->toISOString(),
            'end_date' => optional($code->end_date)?->toISOString(),
            'active' => (bool) $code->is_active,
            'is_active' => (bool) $code->is_active,
            'is_single_use' => (bool) $code->is_single_use,
            'created_at' => optional($code->created_at)?->toISOString(),
            'updated_at' => optional($code->updated_at)?->toISOString(),
        ];
    }

    private function transformBulkDiscount(BulkDiscountRule $rule): array
    {
        return [
            'id' => $rule->id,
            'min_quantity' => (int) $rule->min_quantity,
            'max_quantity' => $rule->max_quantity,
            'discount_percentage' => (float) ($rule->discount_percentage ?? 0),
            'discount_percent' => (float) ($rule->discount_percentage ?? 0),
            'active' => (bool) $rule->is_active,
            'is_active' => (bool) $rule->is_active,
            'created_at' => optional($rule->created_at)?->toISOString(),
            'updated_at' => optional($rule->updated_at)?->toISOString(),
        ];
    }

    private function resolveDiscountTypeId(?int $discountTypeId, ?string $type): int
    {
        if ($discountTypeId) {
            return $discountTypeId;
        }

        $normalizedType = Str::lower(trim((string) $type));
        $targetName = in_array($normalizedType, ['fixed', 'monto fijo', 'fixed_amount'], true)
            ? 'fixed_amount'
            : 'percentage';

        $discountType = DiscountType::query()->firstOrCreate(
            ['name' => $targetName],
            [
                'description' => $targetName === 'fixed_amount'
                    ? 'Descuento de valor fijo.'
                    : 'Descuento porcentual.',
                'is_active' => true,
            ],
        );

        return (int) $discountType->id;
    }
}
