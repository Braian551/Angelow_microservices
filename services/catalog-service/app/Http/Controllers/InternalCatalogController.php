<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InternalCatalogController extends Controller
{
    public function product(int $id): JsonResponse
    {
        $product = DB::table('products as p')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                    ->where('pi.is_primary', '=', true);
            })
            ->where('p.id', $id)
            ->where('p.is_active', true)
            ->select([
                'p.id',
                'p.name',
                'p.slug',
                'pi.image_path as primary_image',
            ])
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        return response()->json(['data' => (array) $product]);
    }

    public function variant(int $id): JsonResponse
    {
        $stockColumn = $this->resolveStockColumn();

        $variant = DB::table('product_size_variants as psv')
            ->join('product_color_variants as pcv', 'psv.color_variant_id', '=', 'pcv.id')
            ->leftJoin('variant_images as vi', function ($join) {
                $join->on('pcv.id', '=', 'vi.color_variant_id')
                    ->on('pcv.product_id', '=', 'vi.product_id')
                    ->where('vi.is_primary', '=', true);
            })
            ->leftJoin('sizes as s', 'psv.size_id', '=', 's.id')
            ->leftJoin('colors as c', 'pcv.color_id', '=', 'c.id')
            ->where('psv.id', $id)
            ->where('psv.is_active', true)
            ->select([
                'psv.id',
                'psv.color_variant_id',
                'pcv.product_id',
                'psv.price',
                'psv.compare_price',
                DB::raw("COALESCE(psv.{$stockColumn}, 0) as quantity"),
                's.name as size_name',
                'c.name as color_name',
                'c.hex_code as color_hex',
                'vi.image_path as variant_image',
            ])
            ->first();

        if (!$variant) {
            return response()->json(['message' => 'Variante no encontrada'], 404);
        }

        $payload = (array) $variant;
        $payload['quantity'] = $this->resolveRealtimeAvailableStock(
            (int) $variant->id,
            (int) ($payload['quantity'] ?? 0),
        );

        return response()->json(['data' => $payload]);
    }

    public function commitInventory(Request $request): JsonResponse
    {
        $stockColumn = $this->resolveStockColumn();
        if ($stockColumn === '') {
            return response()->json([
                'success' => false,
                'message' => 'No existe columna de stock en product_size_variants.',
            ], 422);
        }

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.size_variant_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'strict_reservation' => ['nullable', 'boolean'],
        ]);

        $grouped = [];
        foreach ($data['items'] as $item) {
            $variantId = (int) ($item['size_variant_id'] ?? 0);
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($variantId <= 0 || $productId <= 0 || $quantity <= 0) {
                continue;
            }

            if (!array_key_exists($variantId, $grouped)) {
                $grouped[$variantId] = [
                    'size_variant_id' => $variantId,
                    'product_id' => $productId,
                    'quantity' => 0,
                ];
            }

            $grouped[$variantId]['quantity'] += $quantity;
        }

        if (empty($grouped)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ítems válidos para confirmar inventario.',
            ], 422);
        }

        $processed = [];

        DB::beginTransaction();

        try {
            foreach ($grouped as $groupedItem) {
                $variantId = (int) $groupedItem['size_variant_id'];
                $requiredQty = (int) $groupedItem['quantity'];
                $productId = (int) $groupedItem['product_id'];

                $variant = DB::table('product_size_variants')
                    ->where('id', $variantId)
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Variante {$variantId} no encontrada para confirmación de inventario.",
                    ], 404);
                }

                $currentStock = (int) ($variant->{$stockColumn} ?? 0);
                if ($currentStock < $requiredQty) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para la variante {$variantId}. Disponible: {$currentStock}, solicitado: {$requiredQty}.",
                    ], 409);
                }

                $newStock = $currentStock - $requiredQty;
                $updatePayload = [$stockColumn => $newStock];
                if (Schema::hasColumn('product_size_variants', 'updated_at')) {
                    $updatePayload['updated_at'] = now();
                }

                DB::table('product_size_variants')
                    ->where('id', $variantId)
                    ->update($updatePayload);

                if (Schema::hasTable('stock_history')) {
                    DB::table('stock_history')->insert([
                        'variant_id' => $variantId,
                        'user_id' => 'system-order-service',
                        'previous_qty' => $currentStock,
                        'new_qty' => $newStock,
                        'operation' => 'order_commit',
                        'notes' => 'Descuento por confirmación de pedido #' . (string) $data['order_id'],
                        'created_at' => now(),
                    ]);
                }

                $processed[] = [
                    'product_id' => $productId,
                    'size_variant_id' => $variantId,
                    'committed_quantity' => $requiredQty,
                    'stock_before' => $currentStock,
                    'stock_after' => $newStock,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventario confirmado correctamente.',
                'data' => [
                    'order_id' => (int) $data['order_id'],
                    'processed' => $processed,
                ],
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'No fue posible confirmar inventario en este momento.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private function resolveStockColumn(): string
    {
        foreach (['stock', 'quantity'] as $column) {
            if (Schema::hasColumn('product_size_variants', $column)) {
                return $column;
            }
        }

        return '';
    }

    private function resolveRealtimeAvailableStock(int $sizeVariantId, int $fallbackQuantity): int
    {
        $safeFallback = max(0, $fallbackQuantity);

        try {
            $stockValue = Redis::get("stock:{$sizeVariantId}");
            if ($stockValue !== null && is_numeric((string) $stockValue)) {
                return max(0, (int) $stockValue);
            }

            $reservedValue = Redis::get("reserved:{$sizeVariantId}");
            if ($reservedValue !== null && is_numeric((string) $reservedValue)) {
                $reserved = max(0, (int) $reservedValue);
                return max(0, $safeFallback - $reserved);
            }
        } catch (Throwable) {
            // Fallback a inventario en base de datos si Redis no esta disponible.
        }

        return $safeFallback;
    }
}
