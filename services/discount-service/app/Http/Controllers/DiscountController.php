<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Controlador público del dominio de descuentos.
 * Expone endpoints para listar códigos de descuento vigentes,
 * validar cupones y calcular descuentos por cantidad.
 * Soporta doble origen de datos (microservicio y legacy) durante la migración.
 */
class DiscountController extends Controller
{
    /**
     * Lista los códigos de descuento públicos vigentes.
     * Consulta primero la tabla local del microservicio;
     * si está vacía, hace fallback a la base legacy.
     */
    public function listCodes(): JsonResponse
    {
        $codes = DB::table('discount_codes')->orderByDesc('created_at')->limit(100)->get();

        // Si no hay códigos en microservicio, intenta cargar desde la base legacy como respaldo.
        if ($codes->isEmpty() && $this->legacyTableExists('discount_codes')) {
            try {
                $codes = DB::connection('legacy_mysql')
                    ->table('discount_codes')
                    ->orderByDesc('created_at')
                    ->limit(100)
                    ->get();
            } catch (\Throwable) {
                // Si falla la consulta legacy, retorna colección vacía sin interrumpir la respuesta.
                $codes = collect();
            }
        }

        return response()->json(['data' => $codes]);
    }

    /**
     * Valida un cupón contra: estado activo, fechas de vigencia,
     * límite de usos totales y, si es de uso único, que el usuario no lo haya usado antes.
     * También resuelve descuento por cantidad si se envía item_count.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:20'],
            'user_id' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    // Rechaza user_id si es un array u objeto (debe ser string escalar).
                    if (is_array($value) || is_object($value)) {
                        $fail('El campo user_id no es valido.');

                        return;
                    }

                    // Valida longitud máxima del identificador de usuario.
                    if (Str::length(trim((string) $value)) > 64) {
                        $fail('El campo user_id no debe ser mayor a 64 caracteres.');
                    }
                },
            ],
            'order_total' => ['nullable', 'numeric', 'min:0'],
            'item_count' => ['nullable', 'integer', 'min:0'],
        ]);

        // Si los datos de entrada no pasan la validación, retorna errores 422.
        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Datos de validacion invalidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Busca el cupón activo (local o legacy).
        $discount = $this->findActiveCode((string) $data['code']);

        // Código no encontrado en microservicio ni en legacy.
        if (!$discount) {
            return response()->json(['valid' => false, 'message' => 'Codigo no valido'], 404);
        }

        // Valida fecha de inicio: el cupón aún no está disponible si la fecha es futura.
        $startDate = $this->parseDate($discount->start_date ?? null);
        if ($startDate && now()->lt($startDate)) {
            return response()->json(['valid' => false, 'message' => 'Codigo aun no disponible'], 422);
        }

        // Valida fecha de expiración: el código ya no es válido si la fecha ya pasó.
        $endDate = $this->parseDate($discount->end_date ?? null);
        if ($endDate && now()->greaterThan($endDate)) {
            return response()->json(['valid' => false, 'message' => 'Codigo expirado'], 422);
        }

        // Valida límite de usos totales: verifica que no se haya alcanzado el máximo permitido.
        if ($discount->max_uses !== null && (int) $discount->used_count >= (int) $discount->max_uses) {
            return response()->json(['valid' => false, 'message' => 'Codigo sin cupos'], 422);
        }

        // Si es de uso único, verifica que el usuario autenticado no haya usado este cupón antes.
        $userId = trim((string) ($data['user_id'] ?? ''));
        if ((bool) ($discount->is_single_use ?? false) && $userId !== '' && $this->alreadyUsedByUser(
            (int) $discount->id,
            $userId,
            (bool) ($discount->from_legacy ?? false),
        )) {
            return response()->json(['valid' => false, 'message' => 'Codigo ya usado por este cliente'], 422);
        }

        // Normaliza total de orden y cantidad de ítems a valores no negativos.
        $orderTotal = max(0, (float) ($data['order_total'] ?? 0));
        $itemCount = max(0, (int) ($data['item_count'] ?? 0));

        return response()->json([
            'valid' => true,
            'discount' => $this->formatCodeDiscount($discount, $orderTotal),
            'bulk_discount' => $itemCount > 0 ? $this->resolveBulkDiscount($itemCount, $orderTotal) : null,
        ]);
    }

    /**
     * Calcula si una orden califica para descuento por cantidad (volumen).
     * Retorna la mejor regla aplicable según la cantidad de ítems.
     */
    public function validateBulkDiscount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_count' => ['required', 'integer', 'min:1'],
            'order_total' => ['required', 'numeric', 'min:0'],
        ]);

        // Valida que item_count y order_total sean numéricos positivos.
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

        // No se encontró ninguna regla de descuento por cantidad aplicable.
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

    /**
     * Busca un cupón activo en la fuente principal (microservicio).
     * Si no lo encuentra, busca en la base legacy como respaldo.
     */
    private function findActiveCode(string $code): ?object
    {
        $normalizedCode = Str::lower(trim($code));

        // Búsqueda en microservicio: busca el código activo con su tipo de descuento.
        $discount = DB::table('discount_codes as dc')
            ->leftJoin('discount_types as dt', 'dc.discount_type_id', '=', 'dt.id')
            ->select('dc.*', 'dt.name as discount_type_name')
            ->whereRaw('LOWER(dc.code) = ?', [$normalizedCode])
            ->where('dc.is_active', true)
            ->first();

        if ($discount) {
            // Marca el resultado como originado en microservicio para trazabilidad.
            $discount->source = 'microservice';
            $discount->from_legacy = false;

            return $discount;
        }

        // Fallback a legacy: intenta buscar en la base de datos heredada si existe.
        if (!$this->legacyTableExists('discount_codes')) {
            // La tabla legacy no existe, no se puede hacer fallback.
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
                // Tampoco existe en legacy: el código no es válido en ninguna fuente.
                return null;
            }

            // Marca el resultado como legacy para que el frontend conozca el origen.
            $legacyDiscount->source = 'legacy';
            $legacyDiscount->from_legacy = true;

            return $legacyDiscount;
        } catch (\Throwable) {
            // Error de conexión con legacy: retorna null para no interrumpir el flujo.
            return null;
        }
    }

    /**
     * Comprueba si un cupón de uso único ya fue aplicado por el usuario.
     * Consulta la tabla discount_code_usage en la fuente correspondiente.
     */
    private function alreadyUsedByUser(int $discountCodeId, string $userId, bool $legacySource): bool
    {
        // Descarta parámetros inválidos antes de ejecutar la consulta.
        if ($discountCodeId <= 0 || trim($userId) === '') {
            return false;
        }

        // Si la fuente es legacy, verifica que la tabla de uso exista antes de consultar.
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
            // Error al consultar la tabla de uso: asume que no ha sido usado.
            return false;
        }
    }

    /**
     * Resuelve la mejor regla de descuento por cantidad para la orden actual.
     * Busca la regla que cumpla con el rango de cantidad y tenga mayor prioridad.
     */
    private function resolveBulkDiscount(int $itemCount, float $orderTotal): ?array
    {
        // Sin ítems en la orden, no aplica descuento por cantidad.
        if ($itemCount <= 0) {
            return null;
        }

        // Búsqueda en microservicio: regla activa que cubra la cantidad de ítems.
        $rule = DB::table('bulk_discount_rules')
            ->where('is_active', true)
            ->where('min_quantity', '<=', $itemCount)
            ->where(function ($query) use ($itemCount) {
                // max_quantity null significa "sin límite superior".
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $itemCount);
            })
            ->orderByDesc('min_quantity')
            ->orderByDesc('discount_percentage')
            ->first();

        $source = 'microservice';

        // Fallback a legacy si no hay reglas en microservicio.
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
                // Error de conexión con legacy: ignora y continúa sin regla.
                $rule = null;
            }
        }

        // No se encontró ninguna regla aplicable en ninguna fuente.
        if (!$rule) {
            return null;
        }

        // Limita el porcentaje entre 0 y 100, luego calcula el monto del descuento.
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

    /**
     * Calcula el monto descontado y arma la respuesta pública del cupón.
     */
    private function formatCodeDiscount(object $discount, float $orderTotal): array
    {
        // Determina si el descuento es fijo o porcentual según el tipo asociado.
        $discountType = $this->resolveCodeType($discount);
        // Normaliza el valor del descuento a un número no negativo.
        $discountValue = max(0, (float) ($discount->discount_value ?? 0));

        // Calcula el monto descontado según el tipo: fijo (topeado al total) o porcentual.
        $discountAmount = $discountType === 'fixed'
            ? min($orderTotal, $discountValue)                    // Descuento fijo: no puede exceder el total
            : round(($orderTotal * min(100, $discountValue)) / 100, 2); // Porcentual: sobre el total

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

    /**
     * Determina si un cupón es porcentual o de monto fijo
     * basándose en el nombre del tipo de descuento.
     */
    private function resolveCodeType(object $discount): string
    {
        $typeName = Str::lower((string) ($discount->discount_type_name ?? ''));

        // Verifica si el nombre del tipo contiene palabras clave de monto fijo (fixed, fijo, monto).
        if (
            str_contains($typeName, 'fixed')
            || str_contains($typeName, 'fijo')
            || str_contains($typeName, 'monto')
        ) {
            return 'fixed';
        }

        // Por defecto, asume descuento porcentual.
        return 'percent';
    }

    /**
     * Genera una etiqueta legible para el rango de cantidades de una regla.
     * Ejemplos: "3+ unidades", "3 a 5 unidades".
     */
    private function formatQuantityLabel(int $minQuantity, ?int $maxQuantity): string
    {
        // Sin límite máximo: muestra como "3+ unidades".
        if ($maxQuantity === null) {
            return sprintf('%d+ unidades', $minQuantity);
        }

        // Con rango definido: muestra como "3 a 5 unidades".
        return sprintf('%d a %d unidades', $minQuantity, $maxQuantity);
    }

    /**
     * Convierte valores de fecha heterogéneos en objetos Carbon seguros.
     * Retorna null si el valor no es una fecha válida.
     */
    private function parseDate(mixed $value): ?Carbon
    {
        // Valor nulo o vacío: no hay fecha que parsear.
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            // Si el valor no es una fecha válida, retorna null.
            return null;
        }
    }

    /**
     * Normaliza fechas a ISO para que frontend y APIs reciban un formato estable.
     */
    private function toIsoString(mixed $value): ?string
    {
        $date = $this->parseDate($value);

        // Retorna la fecha en formato ISO si es válida, o null si no.
        return $date ? $date->toISOString() : null;
    }

    /**
     * Comprueba existencia de tablas legacy antes de consultar datos migrados.
     */
    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection('legacy_mysql')->hasTable($table);
        } catch (\Throwable) {
            // Error de conexión con legacy: asume que la tabla no existe.
            return false;
        }
    }
}
