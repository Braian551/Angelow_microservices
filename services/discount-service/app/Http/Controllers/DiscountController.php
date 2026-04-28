<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    public function listCodes(): JsonResponse
    {
        $codes = DB::table('discount_codes')->orderByDesc('created_at')->limit(100)->get();

        if ($codes->isEmpty() && $this->legacyTableExists('discount_codes')) {
            try {
                $codes = DB::connection('legacy_mysql')
                    ->table('discount_codes')
                    ->orderByDesc('created_at')
                    ->limit(100)
                    ->get();
            } catch (\Throwable) {
                $codes = collect();
            }
        }

        return response()->json(['data' => $codes]);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:20'],
            'user_id' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_array($value) || is_object($value)) {
                        $fail('El campo user_id no es valido.');

                        return;
                    }

                    if (Str::length(trim((string) $value)) > 64) {
                        $fail('El campo user_id no debe ser mayor a 64 caracteres.');
                    }
                },
            ],
            'order_total' => ['nullable', 'numeric', 'min:0'],
            'item_count' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Datos de validacion invalidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $discount = $this->findActiveCode((string) $data['code']);

        if (!$discount) {
            return response()->json(['valid' => false, 'message' => 'Codigo no valido'], 404);
        }

        $startDate = $this->parseDate($discount->start_date ?? null);
        if ($startDate && now()->lt($startDate)) {
            return response()->json(['valid' => false, 'message' => 'Codigo aun no disponible'], 422);
        }

        $endDate = $this->parseDate($discount->end_date ?? null);
        if ($endDate && now()->greaterThan($endDate)) {
            return response()->json(['valid' => false, 'message' => 'Codigo expirado'], 422);
        }

        if ($discount->max_uses !== null && (int) $discount->used_count >= (int) $discount->max_uses) {
            return response()->json(['valid' => false, 'message' => 'Codigo sin cupos'], 422);
        }

        $userId = trim((string) ($data['user_id'] ?? ''));
        if ((bool) ($discount->is_single_use ?? false) && $userId !== '' && $this->alreadyUsedByUser(
            (int) $discount->id,
            $userId,
            (bool) ($discount->from_legacy ?? false),
        )) {
            return response()->json(['valid' => false, 'message' => 'Codigo ya usado por este cliente'], 422);
        }

        $orderTotal = max(0, (float) ($data['order_total'] ?? 0));
        $itemCount = max(0, (int) ($data['item_count'] ?? 0));

        return response()->json([
            'valid' => true,
            'discount' => $this->formatCodeDiscount($discount, $orderTotal),
            'bulk_discount' => $itemCount > 0 ? $this->resolveBulkDiscount($itemCount, $orderTotal) : null,
        ]);
    }

    public function validateBulkDiscount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_count' => ['required', 'integer', 'min:1'],
            'order_total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'bulk_discount' => null,
                'message' => 'Datos de validacion invalidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $itemCount = (int) $data['item_count'];
        $orderTotal = (float) $data['order_total'];
        $bulkDiscount = $this->resolveBulkDiscount($itemCount, $orderTotal);

        if (!$bulkDiscount) {
            return response()->json([
                'valid' => false,
                'bulk_discount' => null,
                'message' => 'No hay descuento por cantidad para esta compra.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'bulk_discount' => $bulkDiscount,
        ]);
    }

    private function findActiveCode(string $code): ?object
    {
        $normalizedCode = Str::lower(trim($code));

        $discount = DB::table('discount_codes as dc')
            ->leftJoin('discount_types as dt', 'dc.discount_type_id', '=', 'dt.id')
            ->select('dc.*', 'dt.name as discount_type_name')
            ->whereRaw('LOWER(dc.code) = ?', [$normalizedCode])
            ->where('dc.is_active', true)
            ->first();

        if ($discount) {
            $discount->source = 'microservice';
            $discount->from_legacy = false;

            return $discount;
        }

        if (!$this->legacyTableExists('discount_codes')) {
            return null;
        }

        try {
            $legacyDiscount = DB::connection('legacy_mysql')
                ->table('discount_codes as dc')
                ->leftJoin('discount_types as dt', 'dc.discount_type_id', '=', 'dt.id')
                ->select('dc.*', 'dt.name as discount_type_name')
                ->whereRaw('LOWER(dc.code) = ?', [$normalizedCode])
                ->where('dc.is_active', true)
                ->first();

            if (!$legacyDiscount) {
                return null;
            }

            $legacyDiscount->source = 'legacy';
            $legacyDiscount->from_legacy = true;

            return $legacyDiscount;
        } catch (\Throwable) {
            return null;
        }
    }

    private function alreadyUsedByUser(int $discountCodeId, string $userId, bool $legacySource): bool
    {
        if ($discountCodeId <= 0 || trim($userId) === '') {
            return false;
        }

        if ($legacySource && !$this->legacyTableExists('discount_code_usage')) {
            return false;
        }

        try {
            $query = $legacySource
                ? DB::connection('legacy_mysql')->table('discount_code_usage')
                : DB::table('discount_code_usage');

            return $query
                ->where('discount_code_id', $discountCodeId)
                ->where('user_id', $userId)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveBulkDiscount(int $itemCount, float $orderTotal): ?array
    {
        if ($itemCount <= 0) {
            return null;
        }

        $rule = DB::table('bulk_discount_rules')
            ->where('is_active', true)
            ->where('min_quantity', '<=', $itemCount)
            ->where(function ($query) use ($itemCount) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $itemCount);
            })
            ->orderByDesc('min_quantity')
            ->orderByDesc('discount_percentage')
            ->first();

        $source = 'microservice';

        if (!$rule && $this->legacyTableExists('bulk_discount_rules')) {
            try {
                $rule = DB::connection('legacy_mysql')
                    ->table('bulk_discount_rules')
                    ->where('is_active', true)
                    ->where('min_quantity', '<=', $itemCount)
                    ->where(function ($query) use ($itemCount) {
                        $query->whereNull('max_quantity')
                            ->orWhere('max_quantity', '>=', $itemCount);
                    })
                    ->orderByDesc('min_quantity')
                    ->orderByDesc('discount_percentage')
                    ->first();

                if ($rule) {
                    $source = 'legacy';
                }
            } catch (\Throwable) {
                $rule = null;
            }
        }

        if (!$rule) {
            return null;
        }

        $discountPercentage = max(0, min(100, (float) ($rule->discount_percentage ?? 0)));
        $discountAmount = round(($orderTotal * $discountPercentage) / 100, 2);

        $minQuantity = (int) ($rule->min_quantity ?? 0);
        $maxQuantity = $rule->max_quantity !== null ? (int) $rule->max_quantity : null;

        return [
            'id' => (int) $rule->id,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'label' => $this->formatQuantityLabel($minQuantity, $maxQuantity),
            'source' => $source,
        ];
    }

    private function formatCodeDiscount(object $discount, float $orderTotal): array
    {
        $discountType = $this->resolveCodeType($discount);
        $discountValue = max(0, (float) ($discount->discount_value ?? 0));

        $discountAmount = $discountType === 'fixed'
            ? min($orderTotal, $discountValue)
            : round(($orderTotal * min(100, $discountValue)) / 100, 2);

        return [
            'id' => (int) $discount->id,
            'code' => (string) ($discount->code ?? ''),
            'discount_type' => $discountType,
            'type' => $discountType,
            'discount_type_name' => (string) ($discount->discount_type_name ?? ''),
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'max_uses' => $discount->max_uses !== null ? (int) $discount->max_uses : null,
            'used_count' => (int) ($discount->used_count ?? 0),
            'is_single_use' => (bool) ($discount->is_single_use ?? false),
            'start_date' => $this->toIsoString($discount->start_date ?? null),
            'end_date' => $this->toIsoString($discount->end_date ?? null),
            'source' => (string) ($discount->source ?? 'microservice'),
        ];
    }

    private function resolveCodeType(object $discount): string
    {
        $typeName = Str::lower((string) ($discount->discount_type_name ?? ''));

        if (
            str_contains($typeName, 'fixed')
            || str_contains($typeName, 'fijo')
            || str_contains($typeName, 'monto')
        ) {
            return 'fixed';
        }

        return 'percent';
    }

    private function formatQuantityLabel(int $minQuantity, ?int $maxQuantity): string
    {
        if ($maxQuantity === null) {
            return sprintf('%d+ unidades', $minQuantity);
        }

        return sprintf('%d a %d unidades', $minQuantity, $maxQuantity);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function toIsoString(mixed $value): ?string
    {
        $date = $this->parseDate($value);

        return $date ? $date->toISOString() : null;
    }

    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection('legacy_mysql')->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
