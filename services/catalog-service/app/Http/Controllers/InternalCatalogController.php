<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
                'psv.quantity',
                's.name as size_name',
                'c.name as color_name',
                'c.hex_code as color_hex',
                'vi.image_path as variant_image',
            ])
            ->first();

        if (!$variant) {
            return response()->json(['message' => 'Variante no encontrada'], 404);
        }

        return response()->json(['data' => (array) $variant]);
    }
}
