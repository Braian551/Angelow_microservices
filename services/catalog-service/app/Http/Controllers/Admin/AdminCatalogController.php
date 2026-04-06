<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Support\SiteSettingsCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

    /**
     * Convierte distintos formatos de entrada a booleano estable.
     */
    private function toBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $default;
    }

    /**
     * Obtiene un arreglo desde JSON o desde el valor ya parseado del request.
     */
    private function arrayInput(Request $request, string $key): array
    {
        $value = $request->input($key);

        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Genera un slug unico para evitar colisiones en productos.
     */
    private function generateUniqueProductSlug(string $name, ?string $requestedSlug = null, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($requestedSlug ?: $name);
        $slug = $baseSlug !== '' ? $baseSlug : 'producto';
        $candidate = $slug;
        $suffix = 2;

        while (true) {
            $query = DB::table('products')->where('slug', $candidate);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if (!$query->exists()) {
                return $candidate;
            }

            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }
    }

    /**
     * Construye el payload de producto usando las columnas reales disponibles.
     */
    private function buildProductUpsertData(array $payload, ?int $ignoreId = null): array
    {
        $nameColumn = $this->firstExistingColumn('products', ['name', 'nombre']);
        $descriptionColumn = $this->firstExistingColumn('products', ['description', 'descripcion']);
        $priceColumn = $this->firstExistingColumn('products', ['price', 'precio']);
        $comparePriceColumn = $this->firstExistingColumn('products', ['compare_price']);
        $activeColumn = $this->firstExistingColumn('products', ['is_active', 'activo']);
        $featuredColumn = $this->firstExistingColumn('products', ['is_featured', 'destacado']);
        $brandColumn = $this->firstExistingColumn('products', ['brand', 'marca']);
        $genderColumn = $this->firstExistingColumn('products', ['gender', 'genero']);
        $collectionTextColumn = $this->firstExistingColumn('products', ['collection', 'coleccion']);
        $materialColumn = $this->firstExistingColumn('products', ['material']);
        $careColumn = $this->firstExistingColumn('products', ['care_instructions', 'instrucciones_cuidado']);
        $collectionIdColumn = $this->firstExistingColumn('products', ['collection_id']);

        $data = [
            'slug' => $this->generateUniqueProductSlug(
                $payload['nombre'],
                $payload['slug'] ?? null,
                $ignoreId,
            ),
            'category_id' => $payload['category_id'] ?? null,
            'updated_at' => now(),
        ];

        if ($nameColumn) {
            $data[$nameColumn] = $payload['nombre'];
        }

        if ($descriptionColumn) {
            $data[$descriptionColumn] = $payload['descripcion'] ?? null;
        }

        if ($priceColumn) {
            $data[$priceColumn] = $payload['precio'];
        }

        if ($comparePriceColumn) {
            $data[$comparePriceColumn] = $payload['compare_price'] ?? null;
        }

        if ($activeColumn) {
            $data[$activeColumn] = $payload['activo'] ? 1 : 0;
        }

        if ($featuredColumn) {
            $data[$featuredColumn] = $payload['is_featured'] ? 1 : 0;
        }

        if ($brandColumn) {
            $data[$brandColumn] = $payload['brand'] ?? null;
        }

        if ($genderColumn) {
            $data[$genderColumn] = $payload['gender'] ?? 'unisex';
        }

        if ($collectionTextColumn) {
            $data[$collectionTextColumn] = $payload['collection'] ?? null;
        }

        if ($materialColumn) {
            $data[$materialColumn] = $payload['material'] ?? null;
        }

        if ($careColumn) {
            $data[$careColumn] = $payload['care_instructions'] ?? null;
        }

        if ($collectionIdColumn) {
            $data[$collectionIdColumn] = $payload['collection_id'] ?? null;
        }

        return $data;
    }

    /**
     * Persiste una imagen en uploads compartidos y retorna la ruta publica.
     */
    private function storeUploadedImage(UploadedFile $file, string $folder = 'productos'): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString() . '.' . $extension;
        $destination = public_path('uploads/' . trim($folder, '/'));

        if (!is_dir($destination)) {
            mkdir($destination, 0775, true);
        }

        $file->move($destination, $filename);

        return '/uploads/' . trim($folder, '/') . '/' . $filename;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }

    private function sliderColumn(string $semantic): ?string
    {
        return match ($semantic) {
            'image' => $this->firstExistingColumn('sliders', ['image_url', 'image']),
            'link' => $this->firstExistingColumn('sliders', ['link_url', 'link']),
            'sort_order' => $this->firstExistingColumn('sliders', ['sort_order', 'order_position']),
            'active' => $this->firstExistingColumn('sliders', ['is_active', 'active']),
            default => null,
        };
    }

    private function transformSlider(object $slider): array
    {
        $image = $slider->image_url ?? $slider->image ?? null;
        $link = $slider->link_url ?? $slider->link ?? null;
        $sortOrder = $slider->sort_order ?? $slider->order_position ?? 0;
        $active = (bool) ($slider->is_active ?? $slider->active ?? false);

        return [
            'id' => (int) $slider->id,
            'title' => $slider->title,
            'subtitle' => $slider->subtitle,
            'image' => $image,
            'image_url' => $image,
            'link' => $link,
            'link_url' => $link,
            'sort_order' => (int) $sortOrder,
            'order_position' => (int) $sortOrder,
            'is_active' => $active,
            'active' => $active,
            'created_at' => $slider->created_at ?? null,
            'updated_at' => $slider->updated_at ?? null,
        ];
    }

    private function deletePublicUpload(?string $path, string $folder): void
    {
        $cleanPath = trim((string) $path);
        if ($cleanPath === '') {
            return;
        }

        $expectedPrefix = '/uploads/' . trim($folder, '/');
        if (!str_starts_with($cleanPath, $expectedPrefix) && !str_starts_with($cleanPath, ltrim($expectedPrefix, '/'))) {
            return;
        }

        $absolutePath = public_path(ltrim($cleanPath, '/'));
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function normalizeSettingValue(string $key, mixed $value, array $definition): string
    {
        $type = $definition['type'] ?? 'string';

        if ($type === 'bool') {
            return $this->toBoolean($value) ? '1' : '0';
        }

        if ($type === 'int') {
            $number = (int) $value;
            if (isset($definition['min'])) {
                $number = max((int) $definition['min'], $number);
            }
            if (isset($definition['max'])) {
                $number = min((int) $definition['max'], $number);
            }

            return (string) $number;
        }

        $clean = trim((string) $value);
        if ($clean === '') {
            return (string) ($definition['default'] ?? '');
        }

        if (!empty($definition['pattern']) && !preg_match($definition['pattern'], $clean)) {
            return (string) ($definition['default'] ?? '');
        }

        if (!empty($definition['max_length'])) {
            $clean = mb_substr($clean, 0, (int) $definition['max_length']);
        }

        return $clean;
    }

    private function settingsValuesWithDefaults(): array
    {
        $definitions = SiteSettingsCatalog::definitions();
        $storedValues = SiteSetting::query()
            ->select(['setting_key', 'setting_value'])
            ->pluck('setting_value', 'setting_key')
            ->all();

        $values = [];
        foreach ($definitions as $key => $definition) {
            $storedValue = $storedValues[$key] ?? ($definition['default'] ?? '');
            if (($definition['type'] ?? 'string') === 'bool') {
                $values[$key] = $this->toBoolean($storedValue, (bool) ($definition['default'] ?? false));
                continue;
            }

            $values[$key] = $storedValue;
        }

        return $values;
    }

    /**
     * Normaliza y valida el payload extendido del formulario admin.
     */
    private function parseProductPayload(Request $request): array
    {
        $baseData = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'numeric', 'min:0.01'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer'],
            'collection_id' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable'],
            'is_featured' => ['nullable'],
            'brand' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'collection' => ['nullable', 'string', 'max:100'],
            'material' => ['nullable', 'string', 'max:100'],
            'care_instructions' => ['nullable', 'string'],
            'main_image_path' => ['nullable', 'string', 'max:255'],
        ]);

        $variants = collect($this->arrayInput($request, 'variants'))
            ->map(function ($variant, $index) {
                $sizes = collect($variant['sizes'] ?? [])->map(function ($size, $sizeIndex) use ($index) {
                    $price = isset($size['price']) && $size['price'] !== '' ? (float) $size['price'] : null;
                    $comparePrice = isset($size['compare_price']) && $size['compare_price'] !== ''
                        ? (float) $size['compare_price']
                        : null;

                    return [
                        'id' => isset($size['id']) && $size['id'] !== '' ? (int) $size['id'] : null,
                        'key' => (string) ($size['key'] ?? ('size-' . $index . '-' . $sizeIndex)),
                        'size_id' => isset($size['size_id']) && $size['size_id'] !== '' ? (int) $size['size_id'] : null,
                        'price' => $price,
                        'compare_price' => $comparePrice,
                        'quantity' => isset($size['quantity']) && $size['quantity'] !== '' ? (int) $size['quantity'] : 0,
                        'sku' => trim((string) ($size['sku'] ?? '')) ?: null,
                        'barcode' => trim((string) ($size['barcode'] ?? '')) ?: null,
                        'is_active' => $this->toBoolean($size['is_active'] ?? true, true),
                    ];
                })->values()->all();

                return [
                    'id' => isset($variant['id']) && $variant['id'] !== '' ? (int) $variant['id'] : null,
                    'key' => (string) ($variant['key'] ?? ('variant-' . $index)),
                    'color_id' => isset($variant['color_id']) && $variant['color_id'] !== '' ? (int) $variant['color_id'] : null,
                    'is_default' => $this->toBoolean($variant['is_default'] ?? false, false),
                    'image_path' => trim((string) ($variant['image_path'] ?? '')) ?: null,
                    'sizes' => $sizes,
                ];
            })
            ->values();

        if ($variants->isEmpty()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Debes registrar al menos una variante.',
            ], 422));
        }

        if (!$variants->contains(fn ($variant) => $variant['is_default'])) {
            $first = $variants->first();
            $first['is_default'] = true;
            $variants[0] = $first;
        }

        foreach ($variants as $index => $variant) {
            if (!$variant['color_id']) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Cada variante debe tener un color seleccionado.',
                    'meta' => ['variant_index' => $index],
                ], 422));
            }

            if (collect($variant['sizes'])->isEmpty()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Cada variante debe tener al menos una talla configurada.',
                    'meta' => ['variant_index' => $index],
                ], 422));
            }

            $duplicateSize = collect($variant['sizes'])
                ->pluck('size_id')
                ->filter()
                ->duplicates()
                ->isNotEmpty();

            if ($duplicateSize) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'No puedes repetir la misma talla dentro de una variante.',
                    'meta' => ['variant_index' => $index],
                ], 422));
            }

            foreach ($variant['sizes'] as $sizeIndex => $size) {
                if (!$size['size_id']) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Cada configuracion de talla debe incluir una talla valida.',
                        'meta' => ['variant_index' => $index, 'size_index' => $sizeIndex],
                    ], 422));
                }

                if ($size['price'] === null || $size['price'] <= 0) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Cada talla debe tener un precio mayor a cero.',
                        'meta' => ['variant_index' => $index, 'size_index' => $sizeIndex],
                    ], 422));
                }

                if ($size['compare_price'] !== null && $size['compare_price'] <= $size['price']) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'El precio comparativo por talla debe ser mayor al precio de venta.',
                        'meta' => ['variant_index' => $index, 'size_index' => $sizeIndex],
                    ], 422));
                }
            }
        }

        $baseComparePrice = isset($baseData['compare_price']) && $baseData['compare_price'] !== ''
            ? (float) $baseData['compare_price']
            : null;

        if ($baseComparePrice !== null && $baseComparePrice <= (float) $baseData['precio']) {
            abort(response()->json([
                'success' => false,
                'message' => 'El precio comparativo general debe ser mayor al precio base.',
            ], 422));
        }

        return [
            ...$baseData,
            'precio' => (float) $baseData['precio'],
            'compare_price' => $baseComparePrice,
            'activo' => $this->toBoolean($baseData['activo'] ?? true, true),
            'is_featured' => $this->toBoolean($baseData['is_featured'] ?? false, false),
            'main_image_path' => trim((string) ($baseData['main_image_path'] ?? '')) ?: null,
            'variants' => $variants->all(),
        ];
    }

    /**
     * Reemplaza imagenes y variantes de un producto de forma consistente.
     */
    private function syncProductStructure(int $productId, array $payload, Request $request): void
    {
        $mainImageFile = $request->file('main_image_file');
        $mainImagePath = $payload['main_image_path'];
        $variantImageFiles = $request->file('variant_image_files', []);

        if ($mainImageFile instanceof UploadedFile) {
            $mainImagePath = $this->storeUploadedImage($mainImageFile);
        }

        if (Schema::hasTable('variant_images')) {
            DB::table('variant_images')->where('product_id', $productId)->delete();
        }

        if (Schema::hasTable('product_images')) {
            DB::table('product_images')->where('product_id', $productId)->delete();
        }

        if (Schema::hasTable('product_size_variants') && Schema::hasTable('product_color_variants')) {
            $colorVariantIds = DB::table('product_color_variants')
                ->where('product_id', $productId)
                ->pluck('id');

            if ($colorVariantIds->isNotEmpty()) {
                DB::table('product_size_variants')->whereIn('color_variant_id', $colorVariantIds)->delete();
            }
        }

        if (Schema::hasTable('product_color_variants')) {
            DB::table('product_color_variants')->where('product_id', $productId)->delete();
        }

        if ($mainImagePath && Schema::hasTable('product_images')) {
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'color_variant_id' => null,
                'image_path' => $mainImagePath,
                'alt_text' => $payload['nombre'],
                'order' => 0,
                'is_primary' => true,
                'created_at' => now(),
            ]);
        }

        foreach ($payload['variants'] as $variantIndex => $variant) {
            $colorVariantId = DB::table('product_color_variants')->insertGetId([
                'product_id' => $productId,
                'color_id' => $variant['color_id'],
                'is_default' => $variant['is_default'] ? 1 : 0,
            ]);

            $variantImagePath = $variant['image_path'];
            $variantKey = $variant['key'];
            $variantFile = is_array($variantImageFiles) ? ($variantImageFiles[$variantKey] ?? null) : null;

            if ($variantFile instanceof UploadedFile) {
                $variantImagePath = $this->storeUploadedImage($variantFile);
            }

            if ($variantImagePath) {
                if (Schema::hasTable('variant_images')) {
                    DB::table('variant_images')->insert([
                        'color_variant_id' => $colorVariantId,
                        'product_id' => $productId,
                        'image_path' => $variantImagePath,
                        'alt_text' => $payload['nombre'] . ' variante ' . ($variantIndex + 1),
                        'order' => $variantIndex + 1,
                        'is_primary' => true,
                        'created_at' => now(),
                    ]);
                }

                if (Schema::hasTable('product_images')) {
                    DB::table('product_images')->insert([
                        'product_id' => $productId,
                        'color_variant_id' => $colorVariantId,
                        'image_path' => $variantImagePath,
                        'alt_text' => $payload['nombre'] . ' variante ' . ($variantIndex + 1),
                        'order' => $variantIndex + 1,
                        'is_primary' => false,
                        'created_at' => now(),
                    ]);
                }
            }

            foreach ($variant['sizes'] as $size) {
                DB::table('product_size_variants')->insert([
                    'color_variant_id' => $colorVariantId,
                    'size_id' => $size['size_id'],
                    'sku' => $size['sku'],
                    'barcode' => $size['barcode'],
                    'price' => $size['price'],
                    'compare_price' => $size['compare_price'],
                    'quantity' => $size['quantity'],
                    'is_active' => $size['is_active'] ? 1 : 0,
                ]);
            }
        }
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

        if ($request->filled('ids')) {
            $ids = collect(explode(',', $request->string('ids')->toString()))
                ->map(static fn ($id) => trim($id))
                ->filter(static fn ($id) => $id !== '' && ctype_digit($id))
                ->map(static fn ($id) => (int) $id)
                ->unique()
                ->take(200)
                ->values();

            if ($ids->isNotEmpty()) {
                $query->whereIn('p.id', $ids->all());
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
        $variantImages = collect();
        $sizeVariants = collect();
        $totalStock = 0;
        $minPrice = 0;
        $maxPrice = 0;

        if (Schema::hasTable('product_color_variants')) {
            $colorNameColumn = $this->firstExistingColumn('colors', ['name', 'nombre']);
            $hexColumn = $this->firstExistingColumn('colors', ['hex_code', 'color_hex']);
            $variantsQuery = DB::table('product_color_variants as pcv')
                ->where('pcv.product_id', $id)
                ->select('pcv.*');

            if (Schema::hasTable('colors')) {
                $variantsQuery->leftJoin('colors as clr', 'pcv.color_id', '=', 'clr.id');
                if ($colorNameColumn) {
                    $variantsQuery->addSelect("clr.{$colorNameColumn} as color_name");
                }
                if ($hexColumn) {
                    $variantsQuery->addSelect("clr.{$hexColumn} as hex_code");
                }
            }

            $variants = $variantsQuery->get();
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
            $comparePriceCol = $this->firstExistingColumn('product_size_variants', ['compare_price']);
            $stockCol = $this->firstExistingColumn('product_size_variants', ['quantity', 'stock']);
            $activeCol = $this->firstExistingColumn('product_size_variants', ['is_active', 'activo']);

            $svQuery = DB::table('product_size_variants as psv')
                ->join('product_color_variants as pcv', 'psv.color_variant_id', '=', 'pcv.id')
                ->where('pcv.product_id', $id)
                ->select('psv.id', 'psv.color_variant_id', 'psv.size_id', 'psv.sku', 'psv.barcode');

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
            if ($comparePriceCol) $svQuery->addSelect("psv.{$comparePriceCol} as compare_price");
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

        if (Schema::hasTable('variant_images')) {
            $variantImages = DB::table('variant_images')
                ->where('product_id', $id)
                ->orderByDesc('is_primary')
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->map(function ($img) {
                    $img->url = $img->image_path ?? null;
                    return $img;
                });
        }

        if ($variants->isNotEmpty()) {
            $sizesByVariant = $sizeVariants->groupBy('color_variant_id');
            $imagesByVariant = $variantImages->groupBy('color_variant_id');

            $variants = $variants->map(function ($variant) use ($sizesByVariant, $imagesByVariant) {
                $variant->size_variants = $sizesByVariant->get($variant->id, collect())->values();
                $variant->images = $imagesByVariant->get($variant->id, collect())->values();
                return $variant;
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'variants' => $variants,
                'images' => $images,
                'variant_images' => $variantImages,
                'size_variants' => $sizeVariants,
                'total_stock' => $totalStock,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ],
        ]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $payload = $this->parseProductPayload($request);

        $id = DB::transaction(function () use ($payload, $request) {
            $productData = $this->buildProductUpsertData($payload);
            $productData['created_at'] = now();

            $productId = DB::table('products')->insertGetId($productData);
            $this->syncProductStructure($productId, $payload, $request);

            return $productId;
        });

        return response()->json(['success' => true, 'message' => 'Producto creado', 'id' => $id], 201);
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);
        }

        $payload = $this->parseProductPayload($request);

        DB::transaction(function () use ($id, $payload, $request) {
            $productData = $this->buildProductUpsertData($payload, $id);
            DB::table('products')->where('id', $id)->update($productData);
            $this->syncProductStructure($id, $payload, $request);
        });

        return response()->json(['success' => true, 'message' => 'Producto actualizado']);
    }

    public function destroyProduct(int $id): JsonResponse
    {
        $deleted = DB::transaction(function () use ($id) {
            if (Schema::hasTable('variant_images')) {
                DB::table('variant_images')->where('product_id', $id)->delete();
            }

            if (Schema::hasTable('product_images')) {
                DB::table('product_images')->where('product_id', $id)->delete();
            }

            if (Schema::hasTable('product_color_variants') && Schema::hasTable('product_size_variants')) {
                $variantIds = DB::table('product_color_variants')->where('product_id', $id)->pluck('id');
                if ($variantIds->isNotEmpty()) {
                    DB::table('product_size_variants')->whereIn('color_variant_id', $variantIds)->delete();
                }
            }

            if (Schema::hasTable('product_color_variants')) {
                DB::table('product_color_variants')->where('product_id', $id)->delete();
            }

            return DB::table('products')->where('id', $id)->delete();
        });

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
        $nameColumn = $this->firstExistingColumn('categories', ['nombre', 'name']);
        $descriptionColumn = $this->firstExistingColumn('categories', ['descripcion', 'description']);
        $imageColumn = $this->firstExistingColumn('categories', ['imagen', 'image']);
        $activeColumn = $this->firstExistingColumn('categories', ['is_active', 'activo']);
        $query = DB::table('categories')->select('categories.*');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            $query->selectSub(
                DB::table('products')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('products.category_id', 'categories.id'),
                'product_count',
            );
        }

        if ($nameColumn) {
            $query->orderBy($nameColumn);
        } else {
            $query->orderBy('id');
        }

        $categories = $query->get()->map(function ($category) use ($nameColumn, $descriptionColumn, $imageColumn, $activeColumn) {
            $category->name = $nameColumn ? ($category->{$nameColumn} ?? 'Sin nombre') : 'Sin nombre';
            $category->description = $descriptionColumn ? ($category->{$descriptionColumn} ?? null) : null;
            $category->image = $imageColumn ? ($category->{$imageColumn} ?? null) : null;
            $category->is_active = $activeColumn
                ? (bool) ($category->{$activeColumn} ?? true)
                : true;
            $category->product_count = (int) ($category->product_count ?? 0);
            return $category;
        });

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $nameColumn = $this->firstExistingColumn('categories', ['nombre', 'name']);
        $descriptionColumn = $this->firstExistingColumn('categories', ['descripcion', 'description']);
        $imageColumn = $this->firstExistingColumn('categories', ['imagen', 'image']);
        $activeColumn = $this->firstExistingColumn('categories', ['is_active', 'activo']);

        $payload = [
            ($nameColumn ?: 'name') => $data['nombre'],
            'slug' => $data['slug'] ?? Str::slug($data['nombre']),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($descriptionColumn) {
            $payload[$descriptionColumn] = $data['descripcion'] ?? null;
        }

        if ($imageColumn) {
            $payload[$imageColumn] = $data['imagen'] ?? null;
        }

        if ($activeColumn) {
            $payload[$activeColumn] = ($data['activo'] ?? true) ? 1 : 0;
        }

        $id = DB::table('categories')->insertGetId($payload);

        return response()->json(['success' => true, 'message' => 'Categoria creada', 'id' => $id], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $nameColumn = $this->firstExistingColumn('categories', ['nombre', 'name']);
        $descriptionColumn = $this->firstExistingColumn('categories', ['descripcion', 'description']);
        $imageColumn = $this->firstExistingColumn('categories', ['imagen', 'image']);
        $activeColumn = $this->firstExistingColumn('categories', ['is_active', 'activo']);

        $payload = ['updated_at' => now()];
        if ($nameColumn && isset($data['nombre'])) {
            $payload[$nameColumn] = $data['nombre'];
        }
        if ($descriptionColumn && array_key_exists('descripcion', $data)) {
            $payload[$descriptionColumn] = $data['descripcion'];
        }
        if ($imageColumn && array_key_exists('imagen', $data)) {
            $payload[$imageColumn] = $data['imagen'];
        }
        if (array_key_exists('slug', $data)) {
            $payload['slug'] = $data['slug'];
        }
        if ($activeColumn && array_key_exists('activo', $data)) {
            $payload[$activeColumn] = $data['activo'] ? 1 : 0;
        }

        $updated = DB::table('categories')->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Categoria no encontrada'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Categoria actualizada']);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            $productsUsingCategory = DB::table('products')->where('category_id', $id)->count();
            if ($productsUsingCategory > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar una categoria que tiene productos asociados',
                ], 422);
            }
        }

        DB::table('categories')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Categoria eliminada']);
    }

    // ── Colecciones ─────────────────────────────────────────────────

    public function collections(): JsonResponse
    {
        $nameColumn = $this->firstExistingColumn('collections', ['nombre', 'name']);
        $descriptionColumn = $this->firstExistingColumn('collections', ['descripcion', 'description']);
        $imageColumn = $this->firstExistingColumn('collections', ['imagen', 'image']);
        $activeColumn = $this->firstExistingColumn('collections', ['is_active', 'activo']);
        $query = DB::table('collections')->select('collections.*');

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'collection_id')) {
            $query->selectSub(
                DB::table('products')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('products.collection_id', 'collections.id'),
                'product_count',
            );
        }

        if ($nameColumn) {
            $query->orderBy($nameColumn);
        } else {
            $query->orderByDesc('created_at');
        }

        $collections = $query->get()->map(function ($collection) use ($nameColumn, $descriptionColumn, $imageColumn, $activeColumn) {
            $collection->name = $nameColumn ? ($collection->{$nameColumn} ?? 'Sin nombre') : 'Sin nombre';
            $collection->description = $descriptionColumn ? ($collection->{$descriptionColumn} ?? null) : null;
            $collection->image = $imageColumn ? ($collection->{$imageColumn} ?? null) : null;
            $collection->is_active = $activeColumn
                ? (bool) ($collection->{$activeColumn} ?? true)
                : true;
            $collection->product_count = (int) ($collection->product_count ?? 0);
            return $collection;
        });

        return response()->json(['success' => true, 'data' => $collections]);
    }

    public function colors(): JsonResponse
    {
        if (!Schema::hasTable('colors')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $nameColumn = $this->firstExistingColumn('colors', ['name', 'nombre']);
        $hexColumn = $this->firstExistingColumn('colors', ['hex_code', 'color_hex']);
        $activeColumn = $this->firstExistingColumn('colors', ['is_active', 'activo']);

        $query = DB::table('colors')->select('id');
        $query->addSelect($nameColumn ? "{$nameColumn} as name" : DB::raw("'Sin color' as name"));
        $query->addSelect($hexColumn ? "{$hexColumn} as hex_code" : DB::raw('NULL as hex_code'));

        if ($activeColumn) {
            $query->where($activeColumn, 1);
        }

        if ($nameColumn) {
            $query->orderBy($nameColumn);
        }

        $colors = $query->get()->map(function ($color) {
            if (isset($color->name)) {
                // Correccion on-the-fly de strings corruptos generados por legacy
                $color->name = str_replace(
                    ['Caf??', 'Mel??n', 'Marr??n', 'Lim??n', 'Ne??n'],
                    ['Café', 'Melón', 'Marrón', 'Limón', 'Neón'],
                    $color->name
                );
            }
            return $color;
        });

        return response()->json(['success' => true, 'data' => $colors]);
    }

    public function storeCollection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100'],
            'launch_date' => ['nullable', 'date'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $nameColumn = $this->firstExistingColumn('collections', ['nombre', 'name']);
        $descriptionColumn = $this->firstExistingColumn('collections', ['descripcion', 'description']);
        $imageColumn = $this->firstExistingColumn('collections', ['imagen', 'image']);
        $activeColumn = $this->firstExistingColumn('collections', ['is_active', 'activo']);

        $payload = [
            ($nameColumn ?: 'name') => $data['nombre'],
            'slug' => $data['slug'] ?? Str::slug($data['nombre']),
            'launch_date' => $data['launch_date'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($descriptionColumn) {
            $payload[$descriptionColumn] = $data['descripcion'] ?? null;
        }

        if ($imageColumn) {
            $payload[$imageColumn] = $data['imagen'] ?? null;
        }

        if ($activeColumn) {
            $payload[$activeColumn] = ($data['activo'] ?? true) ? 1 : 0;
        }

        $id = DB::table('collections')->insertGetId($payload);

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
            'activo' => ['nullable', 'boolean'],
        ]);

        $nameColumn = $this->firstExistingColumn('collections', ['nombre', 'name']);
        $descriptionColumn = $this->firstExistingColumn('collections', ['descripcion', 'description']);
        $imageColumn = $this->firstExistingColumn('collections', ['imagen', 'image']);
        $activeColumn = $this->firstExistingColumn('collections', ['is_active', 'activo']);

        $payload = ['updated_at' => now()];
        if ($nameColumn && isset($data['nombre'])) {
            $payload[$nameColumn] = $data['nombre'];
        }
        if ($descriptionColumn && array_key_exists('descripcion', $data)) {
            $payload[$descriptionColumn] = $data['descripcion'];
        }
        if ($imageColumn && array_key_exists('imagen', $data)) {
            $payload[$imageColumn] = $data['imagen'];
        }
        if (array_key_exists('slug', $data)) {
            $payload['slug'] = $data['slug'];
        }
        if (array_key_exists('launch_date', $data)) {
            $payload['launch_date'] = $data['launch_date'];
        }
        if ($activeColumn && array_key_exists('activo', $data)) {
            $payload[$activeColumn] = $data['activo'] ? 1 : 0;
        }

        DB::table('collections')->where('id', $id)->update($payload);

        return response()->json(['success' => true, 'message' => 'Coleccion actualizada']);
    }

    public function destroyCollection(int $id): JsonResponse
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'collection_id')) {
            $productsUsingCollection = DB::table('products')->where('collection_id', $id)->count();
            if ($productsUsingCollection > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar una coleccion que tiene productos asociados',
                ], 422);
            }
        }

        DB::table('collections')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Coleccion eliminada']);
    }

    // ── Tallas ──────────────────────────────────────────────────────

    public function sizes(Request $request): JsonResponse
    {
        if (Schema::hasTable('sizes')) {
            $nameColumn = $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']);
            $orderColumn = $this->firstExistingColumn('sizes', ['sort_order', 'order_position', 'orden']);
            $activeColumn = $this->firstExistingColumn('sizes', ['is_active', 'activo']);

            $query = DB::table('sizes')->select('id');
            $query->addSelect($nameColumn ? "{$nameColumn} as name" : DB::raw("'Sin talla' as name"));
            $query->addSelect($orderColumn ? "{$orderColumn} as sort_order" : DB::raw('NULL as sort_order'));
            $descriptionColumn = $this->firstExistingColumn('sizes', ['description', 'descripcion']);
            $query->addSelect($descriptionColumn ? "{$descriptionColumn} as description" : DB::raw('NULL as description'));
            $query->addSelect($activeColumn ? "{$activeColumn} as is_active" : DB::raw('TRUE as is_active'));

            if (Schema::hasTable('product_size_variants') && Schema::hasColumn('product_size_variants', 'size_id')) {
                $query->selectSub(
                    DB::table('product_size_variants')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('product_size_variants.size_id', 'sizes.id'),
                    'product_count',
                );
            }

            if ($activeColumn) {
                if (!$request->boolean('include_inactive')) {
                    $query->where($activeColumn, 1);
                }
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

        if (Schema::hasTable('product_size_variants') && Schema::hasColumn('product_size_variants', 'size_id')) {
            $sizeUsage = DB::table('product_size_variants')->where('size_id', $id)->count();
            if ($sizeUsage > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar una talla que ya esta asociada a variantes',
                ], 422);
            }
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
                'pcv.id as color_variant_id',
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

    public function inventoryHistory(Request $request): JsonResponse
    {
        if (!Schema::hasTable('stock_history')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);
        $colorNameColumn = $this->firstExistingColumn('product_color_variants', ['color_name', 'name']);
        $sizeNameColumn = $this->firstExistingColumn('sizes', ['name', 'nombre', 'size_label']);
        $hasSizeTableJoin = Schema::hasTable('sizes') && Schema::hasColumn('product_size_variants', 'size_id');
        $sizeLabelExpression = 'NULL as size_label';

        if ($hasSizeTableJoin && $sizeNameColumn) {
            $sizeLabelExpression = 's.' . $sizeNameColumn . ' as size_label';
        } elseif (Schema::hasColumn('product_size_variants', 'size_label')) {
            $sizeLabelExpression = 'psv.size_label as size_label';
        }

        $query = DB::table('stock_history as sh')
            ->leftJoin('product_size_variants as psv', 'sh.variant_id', '=', 'psv.id')
            ->leftJoin('product_color_variants as pcv', 'psv.color_variant_id', '=', 'pcv.id')
            ->leftJoin('products as p', 'pcv.product_id', '=', 'p.id')
            ->select(
                'sh.*',
                'psv.color_variant_id',
                'pcv.product_id',
                DB::raw(($productNameColumn ? 'p.' . $productNameColumn : "'Producto'") . ' as product_name'),
                DB::raw(($colorNameColumn ? 'pcv.' . $colorNameColumn : 'NULL') . ' as color_name'),
                DB::raw($sizeLabelExpression)
            );

        if ($hasSizeTableJoin) {
            $query->leftJoin('sizes as s', 'psv.size_id', '=', 's.id');
        }

        if ($request->filled('product_id')) {
            $query->where('pcv.product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('variant_id')) {
            $query->where('sh.variant_id', (int) $request->input('variant_id'));
        }

        $history = $query
            ->orderByDesc('sh.created_at')
            ->limit(100)
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
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

        if (Schema::hasTable('stock_history')) {
            DB::table('stock_history')->insert([
                'variant_id' => $variantId,
                'user_id' => (string) ($request->user()?->id ?? 'admin'),
                'previous_qty' => $currentStock,
                'new_qty' => $newStock,
                'operation' => $data['action'],
                'notes' => $data['reason'] ?? null,
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock actualizado',
            'new_stock' => $newStock,
        ]);
    }

    public function transferStock(Request $request): JsonResponse
    {
        $stockColumn = $this->firstExistingColumn('product_size_variants', ['stock', 'quantity']);
        if (!$stockColumn) {
            return response()->json(['success' => false, 'message' => 'No se encontro columna de stock'], 422);
        }

        $data = $request->validate([
            'source_variant_id' => ['required', 'integer'],
            'target_variant_id' => ['required', 'integer', 'different:source_variant_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $stockColumn, $request) {
            $source = DB::table('product_size_variants')->where('id', $data['source_variant_id'])->lockForUpdate()->first();
            $target = DB::table('product_size_variants')->where('id', $data['target_variant_id'])->lockForUpdate()->first();

            if (!$source || !$target) {
                abort(response()->json(['success' => false, 'message' => 'Variante origen o destino no encontrada'], 404));
            }

            $sourceQty = (int) ($source->{$stockColumn} ?? 0);
            $targetQty = (int) ($target->{$stockColumn} ?? 0);

            if ($sourceQty < $data['quantity']) {
                abort(response()->json(['success' => false, 'message' => 'La variante origen no tiene stock suficiente'], 422));
            }

            DB::table('product_size_variants')->where('id', $source->id)->update([$stockColumn => $sourceQty - $data['quantity']]);
            DB::table('product_size_variants')->where('id', $target->id)->update([$stockColumn => $targetQty + $data['quantity']]);

            if (Schema::hasTable('stock_history')) {
                $userId = (string) ($request->user()?->id ?? 'admin');
                $note = $data['reason'] ?? 'Transferencia entre variantes';

                DB::table('stock_history')->insert([
                    [
                        'variant_id' => $source->id,
                        'user_id' => $userId,
                        'previous_qty' => $sourceQty,
                        'new_qty' => $sourceQty - $data['quantity'],
                        'operation' => 'transfer',
                        'notes' => $note,
                        'created_at' => now(),
                    ],
                    [
                        'variant_id' => $target->id,
                        'user_id' => $userId,
                        'previous_qty' => $targetQty,
                        'new_qty' => $targetQty + $data['quantity'],
                        'operation' => 'transfer',
                        'notes' => $note,
                        'created_at' => now(),
                    ],
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Stock transferido correctamente']);
    }

    // ── Resenas ─────────────────────────────────────────────────────

    public function reviews(Request $request): JsonResponse
    {
        $reviewStatusColumn = $this->firstExistingColumn('product_reviews', ['status']);
        $reviewApprovedColumn = $this->firstExistingColumn('product_reviews', ['is_approved']);
        $reviewVerifiedColumn = $this->firstExistingColumn('product_reviews', ['is_verified']);
        $reviewTitleColumn = $this->firstExistingColumn('product_reviews', ['title']);
        $reviewCommentColumn = $this->firstExistingColumn('product_reviews', ['comment', 'body']);
        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);

        $query = DB::table('product_reviews as pr')
            ->leftJoin('products as p', 'pr.product_id', '=', 'p.id')
            ->select('pr.*');

        if (!$reviewStatusColumn && $reviewApprovedColumn) {
            $query->addSelect(DB::raw("CASE WHEN COALESCE(pr.{$reviewApprovedColumn}, false) THEN 'approved' ELSE 'pending' END as status"));
        }

        if ($productNameColumn) {
            $query->addSelect("p.{$productNameColumn} as product_name");
        }

        $status = trim($request->string('status')->toString());
        if ($status !== '' && $status !== 'all') {

            if ($reviewStatusColumn) {
                $query->where("pr.{$reviewStatusColumn}", $status);
            } elseif ($reviewApprovedColumn) {
                $query->where("pr.{$reviewApprovedColumn}", $status === 'approved');
            }
        }

        if ($request->filled('rating')) {
            $query->where('pr.rating', (int) $request->input('rating'));
        }

        $verified = trim((string) $request->input('verified', ''));
        if ($reviewVerifiedColumn && $verified !== '' && $verified !== 'all') {
            $query->where("pr.{$reviewVerifiedColumn}", filter_var($verified, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $term = '%' . trim($request->string('search')->toString()) . '%';
            $likeOperator = $this->likeOperator();

            $query->where(function ($searchQuery) use ($term, $likeOperator, $reviewTitleColumn, $reviewCommentColumn, $productNameColumn) {
                if ($reviewTitleColumn) {
                    $searchQuery->where("pr.{$reviewTitleColumn}", $likeOperator, $term);
                }

                if ($reviewCommentColumn) {
                    $method = $reviewTitleColumn ? 'orWhere' : 'where';
                    $searchQuery->{$method}("pr.{$reviewCommentColumn}", $likeOperator, $term);
                }

                if ($productNameColumn) {
                    $searchQuery->orWhere("p.{$productNameColumn}", $likeOperator, $term);
                }
            });
        }

        $reviews = $query->orderByDesc('pr.created_at')->limit(200)->get();

        return response()->json(['success' => true, 'data' => $reviews]);
    }

    public function updateReviewStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        if (!array_key_exists('status', $data) && !array_key_exists('is_verified', $data)) {
            return response()->json(['success' => false, 'message' => 'No se enviaron cambios para la reseña'], 422);
        }

        $statusColumn = $this->firstExistingColumn('product_reviews', ['status']);
        $approvedColumn = $this->firstExistingColumn('product_reviews', ['is_approved']);
        $verifiedColumn = $this->firstExistingColumn('product_reviews', ['is_verified']);
        $payload = [];

        if (array_key_exists('status', $data)) {
            if ($statusColumn) {
                $payload[$statusColumn] = $data['status'];
            } elseif ($approvedColumn) {
                $payload[$approvedColumn] = $data['status'] === 'approved';
            }
        }

        if ($verifiedColumn && array_key_exists('is_verified', $data)) {
            $payload[$verifiedColumn] = (bool) $data['is_verified'];
        }

        if (Schema::hasColumn('product_reviews', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $updated = DB::table('product_reviews')->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Resena no encontrada'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Resena actualizada']);
    }

    public function deleteReview(int $id): JsonResponse
    {
        $deleted = DB::table('product_reviews')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Resena no encontrada'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Resena eliminada']);
    }

    // ── Preguntas ───────────────────────────────────────────────────

    public function questions(Request $request): JsonResponse
    {
        $questionAnswerColumn = $this->firstExistingColumn('question_answers', ['answer', 'answer_text']) ?: 'answer';
        $productNameColumn = $this->firstExistingColumn('products', ['nombre', 'name']);

        $query = DB::table('product_questions as pq')
            ->leftJoin('products as p', 'pq.product_id', '=', 'p.id')
            ->select('pq.*');

        if ($productNameColumn) {
            $query->addSelect("p.{$productNameColumn} as product_name");
        }

        if ($request->filled('search')) {
            $term = '%' . trim($request->string('search')->toString()) . '%';
            $likeOperator = $this->likeOperator();

            $query->where(function ($searchQuery) use ($term, $likeOperator, $productNameColumn) {
                $searchQuery->where('pq.question', $likeOperator, $term);

                if ($productNameColumn) {
                    $searchQuery->orWhere("p.{$productNameColumn}", $likeOperator, $term);
                }
            });
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

        $questions = $questions->map(function ($q) use ($answers, $questionAnswerColumn, $request) {
            $q->answers = $answers->get($q->id, collect())->values();
            $q->answer = $q->answers->first()?->{$questionAnswerColumn} ?? null;
            $q->answer_count = $q->answers->count();
            return $q;
        });

        if ($request->filled('answered')) {
            $answered = filter_var($request->input('answered'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($answered !== null) {
                $questions = $questions->filter(static fn ($question) => $answered ? $question->answer_count > 0 : $question->answer_count === 0)->values();
            }
        }

        return response()->json(['success' => true, 'data' => $questions]);
    }

    public function answerQuestion(Request $request, int $questionId): JsonResponse
    {
        $data = $request->validate([
            'answer_text' => ['nullable', 'string', 'max:2000'],
            'answer' => ['nullable', 'string', 'max:2000'],
        ]);

        $answerValue = trim((string) ($data['answer_text'] ?? $data['answer'] ?? ''));
        if ($answerValue === '') {
            return response()->json(['success' => false, 'message' => 'La respuesta es obligatoria'], 422);
        }

        $question = DB::table('product_questions')->where('id', $questionId)->first();

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Pregunta no encontrada'], 404);
        }

        $admin = $request->input('_admin_user', []);

        $answerColumn = $this->firstExistingColumn('question_answers', ['answer', 'answer_text']) ?: 'answer';
        $sellerColumn = $this->firstExistingColumn('question_answers', ['is_seller', 'is_admin']);

        $payload = [
            'question_id' => $questionId,
            'user_id' => $admin['id'] ?? null,
            $answerColumn => $answerValue,
            'created_at' => now(),
        ];

        if ($sellerColumn) {
            $payload[$sellerColumn] = true;
        }

        DB::table('question_answers')->insert($payload);

        return response()->json(['success' => true, 'message' => 'Respuesta enviada']);
    }

    public function deleteQuestion(int $questionId): JsonResponse
    {
        $question = DB::table('product_questions')->where('id', $questionId)->first();

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Pregunta no encontrada'], 404);
        }

        DB::transaction(function () use ($questionId) {
            DB::table('question_answers')->where('question_id', $questionId)->delete();
            DB::table('product_questions')->where('id', $questionId)->delete();
        });

        return response()->json(['success' => true, 'message' => 'Pregunta eliminada']);
    }

    // ── Sliders ─────────────────────────────────────────────────────

    public function sliders(): JsonResponse
    {
        $sortColumn = $this->sliderColumn('sort_order') ?? 'id';

        $sliders = Slider::query()
            ->orderBy($sortColumn)
            ->get()
            ->map(fn (Slider $slider) => $this->transformSlider($slider));

        return response()->json(['success' => true, 'data' => $sliders]);
    }

    public function storeSlider(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'order_position' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image_file' => ['nullable', 'file', 'image', 'max:4096'],
        ]);

        $imagePath = $request->file('image_file') instanceof UploadedFile
            ? $this->storeUploadedImage($request->file('image_file'), 'sliders')
            : $this->nullableTrim($data['image_url'] ?? $data['image'] ?? null);

        if ($imagePath === null) {
            return response()->json(['success' => false, 'message' => 'La imagen es obligatoria al crear un slider.'], 422);
        }

        $payload = [
            'title' => trim($data['title']),
            'subtitle' => $this->nullableTrim($data['subtitle'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (($column = $this->sliderColumn('image')) !== null) {
            $payload[$column] = $imagePath;
        }
        if (($column = $this->sliderColumn('link')) !== null) {
            $payload[$column] = $this->nullableTrim($data['link_url'] ?? $data['link'] ?? null);
        }
        if (($column = $this->sliderColumn('sort_order')) !== null) {
            $payload[$column] = (int) ($data['sort_order'] ?? $data['order_position'] ?? 0);
        }
        if (($column = $this->sliderColumn('active')) !== null) {
            $payload[$column] = $this->toBoolean($data['active'] ?? $data['is_active'] ?? true, true);
        }

        $slider = Slider::query()->create($payload);

        return response()->json(['success' => true, 'message' => 'Slider creado', 'id' => $slider->id], 201);
    }

    public function updateSlider(Request $request, int $id): JsonResponse
    {
        $slider = Slider::query()->find($id);
        if (!$slider) {
            return response()->json(['success' => false, 'message' => 'Slider no encontrado.'], 404);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'order_position' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image_file' => ['nullable', 'file', 'image', 'max:4096'],
        ]);

        $payload = ['updated_at' => now()];

        if (array_key_exists('title', $data)) {
            $payload['title'] = trim($data['title']);
        }
        if (array_key_exists('subtitle', $data)) {
            $payload['subtitle'] = $this->nullableTrim($data['subtitle'] ?? null);
        }
        if (($column = $this->sliderColumn('link')) !== null && (array_key_exists('link_url', $data) || array_key_exists('link', $data))) {
            $payload[$column] = $this->nullableTrim($data['link_url'] ?? $data['link'] ?? null);
        }
        if (($column = $this->sliderColumn('sort_order')) !== null && (array_key_exists('sort_order', $data) || array_key_exists('order_position', $data))) {
            $payload[$column] = (int) ($data['sort_order'] ?? $data['order_position'] ?? 0);
        }
        if (($column = $this->sliderColumn('active')) !== null && (array_key_exists('active', $data) || array_key_exists('is_active', $data))) {
            $payload[$column] = $this->toBoolean($data['active'] ?? $data['is_active'] ?? false);
        }

        if ($request->file('image_file') instanceof UploadedFile && ($column = $this->sliderColumn('image')) !== null) {
            $this->deletePublicUpload((string) ($slider->{$column} ?? ''), 'sliders');
            $payload[$column] = $this->storeUploadedImage($request->file('image_file'), 'sliders');
        } elseif (($column = $this->sliderColumn('image')) !== null && (array_key_exists('image_url', $data) || array_key_exists('image', $data))) {
            $payload[$column] = $this->nullableTrim($data['image_url'] ?? $data['image'] ?? null);
        }

        $slider->fill($payload);
        $slider->save();

        return response()->json(['success' => true, 'message' => 'Slider actualizado']);
    }

    public function destroySlider(int $id): JsonResponse
    {
        $slider = Slider::query()->find($id);
        if (!$slider) {
            return response()->json(['success' => false, 'message' => 'Slider no encontrado.'], 404);
        }

        if (($column = $this->sliderColumn('image')) !== null) {
            $this->deletePublicUpload((string) ($slider->{$column} ?? ''), 'sliders');
        }

        $slider->delete();

        return response()->json(['success' => true, 'message' => 'Slider eliminado']);
    }

    public function toggleSliderStatus(Request $request, int $id): JsonResponse
    {
        $slider = Slider::query()->find($id);
        if (!$slider) {
            return response()->json(['success' => false, 'message' => 'Slider no encontrado.'], 404);
        }

        $activeColumn = $this->sliderColumn('active');
        if ($activeColumn === null) {
            return response()->json(['success' => false, 'message' => 'La tabla de sliders no tiene una columna de estado válida.'], 422);
        }

        $data = $request->validate([
            'active' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slider->{$activeColumn} = $this->toBoolean($data['active'] ?? $data['is_active'] ?? !($slider->{$activeColumn} ?? false));
        $slider->updated_at = now();
        $slider->save();

        return response()->json(['success' => true, 'message' => 'Estado del slider actualizado.']);
    }

    public function reorderSliders(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $sortColumn = $this->sliderColumn('sort_order');
        if ($sortColumn === null) {
            return response()->json(['success' => false, 'message' => 'La tabla de sliders no tiene una columna de orden válida.'], 422);
        }

        DB::transaction(function () use ($data, $sortColumn) {
            foreach ($data['items'] as $item) {
                Slider::query()->whereKey((int) $item['id'])->update([
                    $sortColumn => (int) $item['sort_order'],
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Orden de sliders actualizado.']);
    }

    // ── Configuracion ───────────────────────────────────────────────

    public function settings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $this->settingsValuesWithDefaults(),
                'definitions' => SiteSettingsCatalog::definitions(),
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $definitions = SiteSettingsCatalog::definitions();
        $existingSettings = SiteSetting::query()->get()->keyBy('setting_key');
        $adminUser = $request->input('_admin_user', []);

        foreach ($definitions as $key => $definition) {
            $uploadedFile = $request->file($key);
            $hasScalarValue = $request->exists($key);

            if (!$uploadedFile && !$hasScalarValue) {
                continue;
            }

            if (($definition['type'] ?? 'string') === 'image' && $uploadedFile instanceof UploadedFile) {
                $currentValue = (string) optional($existingSettings->get($key))->setting_value;
                $this->deletePublicUpload($currentValue, 'settings');
                $value = $this->storeUploadedImage($uploadedFile, 'settings');
            } else {
                $value = $this->normalizeSettingValue($key, $request->input($key), $definition);
            }

            SiteSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'category' => $definition['category'] ?? 'general',
                    'updated_by' => (string) ($adminUser['id'] ?? ''),
                    'updated_at' => now(),
                ],
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
