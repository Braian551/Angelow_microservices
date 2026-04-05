<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Controlador admin para productos, categorias, colecciones,
 * tallas, inventario, sliders, configuracion y reportes del catalogo.
 */
class AdminCatalogController extends Controller
{
    /**
     * Operador LIKE compatible con el motor actual.
     */
    private function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    /**
     * Retorna la primera columna existente de una lista de candidatos.
     */
    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    // ── Productos ────────────────────────────────────────────────────

    public function products(Request $request): JsonResponse
    {
        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);
        $productDescriptionColumn = $this->firstExistingColumn('products', ['descripcion', 'description']);
        $productImageColumn = $this->firstExistingColumn('products', ['imagen', 'image', 'image_url']);
        $categoryNameColumn = $this->firstExistingColumn('categories', ['nombre', 'name']);
        $variantStockColumn = $this->firstExistingColumn('product_size_variants', ['quantity', 'stock']);
        $likeOperator = $this->likeOperator();
        $hasProductImagesTable = Schema::hasTable('product_images');
        $hasColorVariantsTable = Schema::hasTable('product_color_variants');
        $hasSizeVariantsTable = Schema::hasTable('product_size_variants');

        $query = DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->select('p.*');

        if ($categoryNameColumn) {
            $query->addSelect("c.{$categoryNameColumn} as category_name");
        }

        if ($hasProductImagesTable) {
            $query->selectSub(
                DB::table('product_images as pi')
                    ->select('pi.image_path')
                    ->whereColumn('pi.product_id', 'p.id')
                    ->orderByDesc('pi.is_primary')
                    ->orderBy('pi.id')
                    ->limit(1),
                'primary_image',
            );
        } else {
            $query->addSelect(DB::raw('NULL as primary_image'));
        }

        if ($productImageColumn) {
            $query->addSelect("p.{$productImageColumn} as product_image");
        }

        if ($hasColorVariantsTable && $hasSizeVariantsTable && $variantStockColumn) {
            // Stock total consolidado
            $query->selectSub(
                DB::table('product_color_variants as pcv')
                    ->leftJoin('product_size_variants as psv', 'psv.color_variant_id', '=', 'pcv.id')
                    ->selectRaw("COALESCE(SUM(psv.{$variantStockColumn}), 0)")
                    ->whereColumn('pcv.product_id', 'p.id'),
                'total_stock',
            );

            // Cantidad de variantes de color
            $query->selectSub(
                DB::table('product_color_variants as pcv2')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('pcv2.product_id', 'p.id'),
                'variant_count',
            );

            // Rango de precios desde variantes
            $variantPriceColumn = $this->firstExistingColumn('product_size_variants', ['price', 'precio']);
            if ($variantPriceColumn) {
                $query->selectSub(
                    DB::table('product_color_variants as pcv3')
                        ->leftJoin('product_size_variants as psv3', 'psv3.color_variant_id', '=', 'pcv3.id')
                        ->selectRaw("MIN(psv3.{$variantPriceColumn})")
                        ->whereColumn('pcv3.product_id', 'p.id'),
                    'min_price',
                );
                $query->selectSub(
                    DB::table('product_color_variants as pcv4')
                        ->leftJoin('product_size_variants as psv4', 'psv4.color_variant_id', '=', 'pcv4.id')
                        ->selectRaw("MAX(psv4.{$variantPriceColumn})")
                        ->whereColumn('pcv4.product_id', 'p.id'),
                    'max_price',
                );
            }
        }

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            if ($productNameColumn || $productDescriptionColumn) {
                $query->where(function ($q) use ($s, $productNameColumn, $productDescriptionColumn, $likeOperator) {
                    if ($productNameColumn) {
                        $q->where("p.{$productNameColumn}", $likeOperator, "%{$s}%");
                    }

                    if ($productDescriptionColumn) {
                        $method = $productNameColumn ? 'orWhere' : 'where';
                        $q->{$method}("p.{$productDescriptionColumn}", $likeOperator, "%{$s}%");
                    }
                });
            }
        }

        if ($request->filled('category')) {
            $query->where('p.category_id', $request->input('category'));
        }

        if ($request->filled('status')) {
            $active = $request->input('status') === 'active';
            if (Schema::hasColumn('products', 'activo')) {
                $query->where('p.activo', $active ? 1 : 0);
            } elseif (Schema::hasColumn('products', 'is_active')) {
                $query->where('p.is_active', $active ? 1 : 0);
            }
        }

        $products = $query->orderByDesc('p.created_at')->limit(200)->get();

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function showProduct(int $id): JsonResponse
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        $variants = collect();
        $images = collect();
        $sizeVariants = collect();
        $totalStock = 0;
        $minPrice = 0;
        $maxPrice = 0;

        if (Schema::hasTable('product_color_variants')) {
            $variants = DB::table('product_color_variants')->where('product_id', $id)->get();
        }
        if (Schema::hasTable('product_images')) {
            // Incluir nombre de color para filtros de galeria
            $imagesQuery = DB::table('product_images as pi')
                ->where('pi.product_id', $id)
                ->select('pi.*');
            if (Schema::hasTable('product_color_variants') && Schema::hasColumn('product_images', 'color_variant_id')) {
                $imagesQuery->leftJoin('product_color_variants as pcv', 'pi.color_variant_id', '=', 'pcv.id');
                $colorNameCol = $this->firstExistingColumn('product_color_variants', ['color_name', 'name']);
                $hexCol = $this->firstExistingColumn('product_color_variants', ['hex_code', 'color_hex']);
                if ($colorNameCol) {
                    $imagesQuery->addSelect("pcv.{$colorNameCol} as color_name");
                }
                if ($hexCol) {
                    $imagesQuery->addSelect("pcv.{$hexCol} as hex_code");
                }
            }
            $images = $imagesQuery->orderByDesc('pi.is_primary')->orderBy('pi.id')->get();

            // Resolver URL completa de cada imagen
            $images = $images->map(function ($img) {
                $img->url = $img->image_path ?? $img->image ?? $img->url ?? null;
                return $img;
            });
        }

        // Variantes de talla con detalle para tabla
        if (Schema::hasTable('product_size_variants') && Schema::hasTable('product_color_variants')) {
            $colorNameCol = $this->firstExistingColumn('product_color_variants', ['color_name', 'name']);
            $sizeNameCol = $this->firstExistingColumn('product_size_variants', ['size_label']);
            $priceCol = $this->firstExistingColumn('product_size_variants', ['price', 'precio']);
            $stockCol = $this->firstExistingColumn('product_size_variants', ['quantity', 'stock']);
            $activeCol = $this->firstExistingColumn('product_size_variants', ['is_active', 'activo']);

            $svQuery = DB::table('product_size_variants as psv')
                ->join('product_color_variants as pcv', 'psv.color_variant_id', '=', 'pcv.id')
                ->where('pcv.product_id', $id)
                ->select('psv.id');

            if ($colorNameCol) $svQuery->addSelect("pcv.{$colorNameCol} as color_name");
            if ($sizeNameCol) {
                $svQuery->addSelect("psv.{$sizeNameCol} as size_name");
            } elseif (Schema::hasTable('sizes') && Schema::hasColumn('product_size_variants', 'size_id')) {
                $sizeTblName = $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']);
                if ($sizeTblName) {
                    $svQuery->leftJoin('sizes as s', 'psv.size_id', '=', 's.id');
                    $svQuery->addSelect("s.{$sizeTblName} as size_name");
                }
            }
            if ($priceCol) $svQuery->addSelect("psv.{$priceCol} as price");
            if ($stockCol) $svQuery->addSelect("psv.{$stockCol} as quantity");
            if ($activeCol) $svQuery->addSelect("psv.{$activeCol} as is_active");

            $sizeVariants = $svQuery->get();

            // Calcular stock total y rango de precios
            if ($stockCol) {
                $totalStock = $sizeVariants->sum('quantity');
            }
            if ($priceCol) {
                $prices = $sizeVariants->pluck('price')->filter()->map(fn($v) => (float) $v);
                $minPrice = $prices->min() ?? 0;
                $maxPrice = $prices->max() ?? 0;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'variants' => $variants,
                'images' => $images,
                'size_variants' => $sizeVariants,
                'total_stock' => $totalStock,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ],
        ]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['nombre']);

        $id = DB::table('products')->insertGetId([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'precio' => $data['precio'],
            'category_id' => $data['category_id'] ?? null,
            'slug' => $slug,
            'activo' => $data['activo'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Producto creado', 'id' => $id], 201);
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['sometimes', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['updated_at'] = now();

        DB::table('products')->where('id', $id)->update($data);

        return response()->json(['success' => true, 'message' => 'Producto actualizado']);
    }

    public function destroyProduct(int $id): JsonResponse
    {
        $deleted = DB::table('products')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Producto eliminado']);
    }

    /**
     * Activar o desactivar un producto.
     */
    public function toggleProductStatus(int $id, Request $request): JsonResponse
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        $activeCol = $this->firstExistingColumn('products', ['is_active', 'activo']);
        if (!$activeCol) {
            return response()->json(['success' => false, 'message' => 'Columna de estado no encontrada'], 500);
        }

        $newStatus = $request->boolean('is_active', !(bool) ($product->$activeCol ?? true));
        DB::table('products')->where('id', $id)->update([
            $activeCol => $newStatus ? 1 : 0,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Producto activado' : 'Producto desactivado',
            'data' => ['is_active' => $newStatus],
        ]);
    }

    // ── Categorias ──────────────────────────────────────────────────

    public function categories(): JsonResponse
    {
        $categories = DB::table('categories')->orderBy('nombre')->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100'],
        ]);

        $id = DB::table('categories')->insertGetId([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'imagen' => $data['imagen'] ?? null,
            'slug' => $data['slug'] ?? Str::slug($data['nombre']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Categoria creada', 'id' => $id], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100'],
        ]);

        $data['updated_at'] = now();
        $updated = DB::table('categories')->where('id', $id)->update($data);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Categoria no encontrada'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Categoria actualizada']);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        DB::table('categories')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Categoria eliminada']);
    }

    // ── Colecciones ─────────────────────────────────────────────────

    public function collections(): JsonResponse
    {
        $collections = DB::table('collections')->orderByDesc('created_at')->get();

        return response()->json(['success' => true, 'data' => $collections]);
    }

    public function storeCollection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100'],
            'launch_date' => ['nullable', 'date'],
        ]);

        $id = DB::table('collections')->insertGetId([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'imagen' => $data['imagen'] ?? null,
            'slug' => $data['slug'] ?? Str::slug($data['nombre']),
            'launch_date' => $data['launch_date'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Coleccion creada', 'id' => $id], 201);
    }

    public function updateCollection(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100'],
            'launch_date' => ['nullable', 'date'],
        ]);

        $data['updated_at'] = now();
        DB::table('collections')->where('id', $id)->update($data);

        return response()->json(['success' => true, 'message' => 'Coleccion actualizada']);
    }

    public function destroyCollection(int $id): JsonResponse
    {
        DB::table('collections')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Coleccion eliminada']);
    }

    // ── Tallas ──────────────────────────────────────────────────────

    public function sizes(): JsonResponse
    {
        if (Schema::hasTable('sizes')) {
            $nameColumn = $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']);
            $orderColumn = $this->firstExistingColumn('sizes', ['sort_order', 'order_position', 'orden']);
            $activeColumn = $this->firstExistingColumn('sizes', ['is_active', 'activo']);

            $query = DB::table('sizes')->select('id');
            $query->addSelect($nameColumn ? "{$nameColumn} as name" : DB::raw("'Sin talla' as name"));
            $query->addSelect($orderColumn ? "{$orderColumn} as sort_order" : DB::raw('NULL as sort_order'));

            if ($activeColumn) {
                $query->where($activeColumn, 1);
            }

            if ($orderColumn) {
                $query->orderBy($orderColumn);
            }

            if ($nameColumn) {
                $query->orderBy($nameColumn);
            }

            $sizes = $query->get();

            return response()->json(['success' => true, 'data' => $sizes]);
        }

        $fallback = collect();

        if (Schema::hasTable('product_size_variants') && Schema::hasColumn('product_size_variants', 'size_label')) {
            $fallback = DB::table('product_size_variants')
                ->select('size_label as name')
                ->distinct()
                ->orderBy('size_label')
                ->get()
                ->values()
                ->map(static function ($row, $index) {
                    return [
                        'id' => $index + 1,
                        'name' => $row->name,
                        'sort_order' => null,
                    ];
                });
        }

        $sizes = $fallback;

        return response()->json(['success' => true, 'data' => $sizes]);
    }

    public function storeSize(Request $request): JsonResponse
    {
        if (!Schema::hasTable('sizes')) {
            return response()->json(['success' => false, 'message' => 'Tabla de tallas no disponible'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $nameColumn = $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']);
        $descriptionColumn = $this->firstExistingColumn('sizes', ['description', 'descripcion']);
        $orderColumn = $this->firstExistingColumn('sizes', ['sort_order', 'order_position', 'orden']);
        $activeColumn = $this->firstExistingColumn('sizes', ['is_active', 'activo']);

        if (!$nameColumn) {
            return response()->json(['success' => false, 'message' => 'No se encontro columna de nombre en tallas'], 422);
        }

        $payload = [$nameColumn => $data['name']];

        if ($descriptionColumn) {
            $payload[$descriptionColumn] = $data['description'] ?? null;
        }

        if ($orderColumn) {
            $payload[$orderColumn] = $data['sort_order'] ?? 0;
        }

        if ($activeColumn) {
            $payload[$activeColumn] = ($data['is_active'] ?? true) ? 1 : 0;
        }

        if (Schema::hasColumn('sizes', 'created_at')) {
            $payload['created_at'] = now();
        }
        if (Schema::hasColumn('sizes', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $id = DB::table('sizes')->insertGetId($payload);

        return response()->json(['success' => true, 'message' => 'Talla creada', 'id' => $id], 201);
    }

    public function updateSize(Request $request, int $id): JsonResponse
    {
        if (!Schema::hasTable('sizes')) {
            return response()->json(['success' => false, 'message' => 'Tabla de tallas no disponible'], 422);
        }

        $size = DB::table('sizes')->where('id', $id)->first();
        if (!$size) {
            return response()->json(['success' => false, 'message' => 'Talla no encontrada'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $nameColumn = $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']);
        $descriptionColumn = $this->firstExistingColumn('sizes', ['description', 'descripcion']);
        $orderColumn = $this->firstExistingColumn('sizes', ['sort_order', 'order_position', 'orden']);
        $activeColumn = $this->firstExistingColumn('sizes', ['is_active', 'activo']);

        $payload = [];
        if ($nameColumn && isset($data['name'])) {
            $payload[$nameColumn] = $data['name'];
        }
        if ($descriptionColumn && array_key_exists('description', $data)) {
            $payload[$descriptionColumn] = $data['description'];
        }
        if ($orderColumn && array_key_exists('sort_order', $data)) {
            $payload[$orderColumn] = $data['sort_order'];
        }
        if ($activeColumn && array_key_exists('is_active', $data)) {
            $payload[$activeColumn] = $data['is_active'] ? 1 : 0;
        }
        if (Schema::hasColumn('sizes', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if ($payload === []) {
            return response()->json(['success' => true, 'message' => 'Sin cambios para actualizar']);
        }

        DB::table('sizes')->where('id', $id)->update($payload);

        return response()->json(['success' => true, 'message' => 'Talla actualizada']);
    }

    public function destroySize(int $id): JsonResponse
    {
        if (!Schema::hasTable('sizes')) {
            return response()->json(['success' => false, 'message' => 'Tabla de tallas no disponible'], 422);
        }

        $deleted = DB::table('sizes')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Talla no encontrada'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Talla eliminada']);
    }

    // ── Inventario ──────────────────────────────────────────────────

    public function inventory(Request $request): JsonResponse
    {
        if (!Schema::hasTable('product_size_variants') || !Schema::hasTable('product_color_variants') || !Schema::hasTable('products')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $likeOperator = $this->likeOperator();
        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);
        $productImageColumn = $this->firstExistingColumn('products', ['imagen', 'image', 'image_url']);
        $colorNameColumn = $this->firstExistingColumn('product_color_variants', ['color_name', 'name']);
        $sizeNameColumn = $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']);
        $stockColumn = $this->firstExistingColumn('product_size_variants', ['stock', 'quantity']);
        $hasSizeId = Schema::hasColumn('product_size_variants', 'size_id');
        $hasSku = Schema::hasColumn('product_size_variants', 'sku');
        $hasInlineSizeLabel = Schema::hasColumn('product_size_variants', 'size_label');

        if (!$stockColumn) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $query = DB::table('product_size_variants as psv')
            ->join('product_color_variants as pcv', 'psv.color_variant_id', '=', 'pcv.id')
            ->join('products as p', 'pcv.product_id', '=', 'p.id')
            ->select(
                'psv.id',
                'p.id as product_id',
                DB::raw(($productNameColumn ? "p.{$productNameColumn}" : "'Producto'") . ' as product_name'),
                DB::raw(($colorNameColumn ? "pcv.{$colorNameColumn}" : 'NULL') . ' as color_name'),
                DB::raw("psv.{$stockColumn} as stock"),
                DB::raw($hasSku ? 'psv.sku' : 'NULL as sku'),
            );

        if ($productImageColumn) {
            $query->addSelect("p.{$productImageColumn} as image");
        }

        if ($hasSizeId && Schema::hasTable('sizes')) {
            $query->leftJoin('sizes as s', 'psv.size_id', '=', 's.id');
            $query->addSelect(DB::raw(($sizeNameColumn ? "s.{$sizeNameColumn}" : 'NULL') . ' as size_label'));
        } elseif ($hasInlineSizeLabel) {
            $query->addSelect('psv.size_label');
        } else {
            $query->addSelect(DB::raw('NULL as size_label'));
        }

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            if ($productNameColumn) {
                $query->where("p.{$productNameColumn}", $likeOperator, "%{$s}%");
            }
        }

        if ($request->input('stock_filter') === 'low') {
            $query->where("psv.{$stockColumn}", '>', 0)->where("psv.{$stockColumn}", '<=', 5);
        } elseif (in_array($request->input('stock_filter'), ['out', 'zero'], true)) {
            $query->where("psv.{$stockColumn}", '<=', 0);
        }

        if ($productNameColumn) {
            $query->orderBy("p.{$productNameColumn}");
        } else {
            $query->orderBy('p.id');
        }

        $items = $query->limit(500)->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function adjustStock(Request $request, int $variantId): JsonResponse
    {
        $stockColumn = $this->firstExistingColumn('product_size_variants', ['stock', 'quantity']);
        if (!$stockColumn) {
            return response()->json(['success' => false, 'message' => 'No se encontro columna de stock'], 422);
        }

        $data = $request->validate([
            'action' => ['required', 'in:add,subtract,set'],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $variant = DB::table('product_size_variants')->where('id', $variantId)->first();

        if (!$variant) {
            return response()->json(['success' => false, 'message' => 'Variante no encontrada'], 404);
        }

        $currentStock = (int) ($variant->{$stockColumn} ?? 0);

        $newStock = match ($data['action']) {
            'add' => $currentStock + $data['quantity'],
            'subtract' => max(0, $currentStock - $data['quantity']),
            'set' => $data['quantity'],
        };

        $payload = [$stockColumn => $newStock];
        if (Schema::hasColumn('product_size_variants', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('product_size_variants')->where('id', $variantId)->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Stock actualizado',
            'new_stock' => $newStock,
        ]);
    }

    // ── Resenas ─────────────────────────────────────────────────────

    public function reviews(Request $request): JsonResponse
    {
        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);

        $query = DB::table('product_reviews as pr')
            ->leftJoin('products as p', 'pr.product_id', '=', 'p.id')
            ->select('pr.*');

        if ($productNameColumn) {
            $query->addSelect("p.{$productNameColumn} as product_name");
        }

        if ($request->filled('status')) {
            $query->where('pr.status', $request->input('status'));
        }

        $reviews = $query->orderByDesc('pr.created_at')->limit(200)->get();

        return response()->json(['success' => true, 'data' => $reviews]);
    }

    public function updateReviewStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);

        $updated = DB::table('product_reviews')->where('id', $id)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Resena no encontrada'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Resena actualizada']);
    }

    // ── Preguntas ───────────────────────────────────────────────────

    public function questions(Request $request): JsonResponse
    {
        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);

        $query = DB::table('product_questions as pq')
            ->leftJoin('products as p', 'pq.product_id', '=', 'p.id')
            ->select('pq.*');

        if ($productNameColumn) {
            $query->addSelect("p.{$productNameColumn} as product_name");
        }

        $questions = $query->orderByDesc('pq.created_at')->limit(200)->get();

        // Adjuntar respuestas
        $questionIds = $questions->pluck('id')->toArray();
        $answers = collect();

        if (!empty($questionIds)) {
            $answers = DB::table('question_answers')
                ->whereIn('question_id', $questionIds)
                ->orderBy('created_at')
                ->get()
                ->groupBy('question_id');
        }

        $questions = $questions->map(function ($q) use ($answers) {
            $q->answers = $answers->get($q->id, collect())->values();
            $q->answer = $q->answers->first()?->answer_text ?? null;
            return $q;
        });

        return response()->json(['success' => true, 'data' => $questions]);
    }

    public function answerQuestion(Request $request, int $questionId): JsonResponse
    {
        $data = $request->validate([
            'answer_text' => ['required', 'string', 'max:2000'],
        ]);

        $question = DB::table('product_questions')->where('id', $questionId)->first();

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Pregunta no encontrada'], 404);
        }

        $admin = $request->input('_admin_user', []);

        DB::table('question_answers')->insert([
            'question_id' => $questionId,
            'user_id' => $admin['id'] ?? null,
            'answer_text' => $data['answer_text'],
            'is_admin' => true,
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Respuesta enviada']);
    }

    // ── Sliders ─────────────────────────────────────────────────────

    public function sliders(): JsonResponse
    {
        $sliders = DB::table('sliders')->orderBy('sort_order')->get();

        return response()->json(['success' => true, 'data' => $sliders]);
    }

    public function storeSlider(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]);

        $id = DB::table('sliders')->insertGetId([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Slider creado', 'id' => $id], 201);
    }

    public function updateSlider(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]);

        $payload = array_filter([
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
            'is_active' => $data['active'] ?? null,
            'updated_at' => now(),
        ], fn($v) => $v !== null);

        DB::table('sliders')->where('id', $id)->update($payload);

        return response()->json(['success' => true, 'message' => 'Slider actualizado']);
    }

    public function destroySlider(int $id): JsonResponse
    {
        DB::table('sliders')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Slider eliminado']);
    }

    // ── Configuracion ───────────────────────────────────────────────

    public function settings(): JsonResponse
    {
        $rows = DB::table('site_settings')->get();
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $settings = $request->all();

        foreach ($settings as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }

            DB::table('site_settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => now()],
            );
        }

        return response()->json(['success' => true, 'message' => 'Configuracion guardada']);
    }

    // ── Reportes de productos ───────────────────────────────────────

    public function reportProducts(Request $request): JsonResponse
    {
        $nameColumn = $this->firstExistingColumn('products', ['name', 'nombre']);
        $soldColumn = $this->firstExistingColumn('products', ['sold_count', 'total_sold']);
        $priceColumn = $this->firstExistingColumn('products', ['price', 'precio']);
        $ratingColumn = $this->firstExistingColumn('products', ['avg_rating']);

        $nameSelect = $nameColumn ? "{$nameColumn} as name" : "'Sin nombre' as name";
        $soldSelect = $soldColumn ? "COALESCE({$soldColumn}, 0) as units_sold" : '0 as units_sold';
        $priceSelect = $priceColumn ? "COALESCE({$priceColumn}, 0) as unit_price" : '0 as unit_price';
        $ratingSelect = $ratingColumn ? "COALESCE({$ratingColumn}, 0) as avg_rating" : '0 as avg_rating';

        $products = DB::table('products')
            ->selectRaw("id, {$nameSelect}, {$soldSelect}, {$priceSelect}, {$ratingSelect}")
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $rows = $products->map(static function ($product) {
            $units = (int) ($product->units_sold ?? 0);
            $price = (float) ($product->unit_price ?? 0);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'units_sold' => $units,
                'revenue' => round($units * $price, 2),
                'avg_rating' => (float) ($product->avg_rating ?? 0),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }
}
