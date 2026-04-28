<?php

namespace App\Repositories;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

/**
 * Query Builder implementation of the Product repository.
 *
 * Migrated from angelow/producto/includes/product-functions.php
 * and angelow/tienda/productos.php (SP GetFilteredProducts).
 */
class QueryBuilderProductRepository implements ProductRepositoryInterface
{
    /**
     * Get a paginated, filtered list of products.
     *
     * Replaces the stored procedure GetFilteredProducts with a
     * Query Builder approach for portability and readability.
     */
    public function getFiltered(array $filters, int $limit, int $offset, ?string $userId = null): array
    {
        $result = $this->runFilteredQueryByConnection(
            null,
            $filters,
            $limit,
            $offset,
            $userId,
        );

        // Fallback legacy durante migración cuando la base distribuida no tenga productos aún.
        if (($result['total'] ?? 0) === 0) {
            try {
                $legacyResult = $this->runFilteredQueryByConnection(
                    'legacy_mysql',
                    $filters,
                    $limit,
                    $offset,
                    $userId,
                );

                if (($legacyResult['total'] ?? 0) > 0) {
                    $result = $legacyResult;
                }
            } catch (Throwable $exception) {
                Log::warning('No se pudo consultar el fallback de catálogo desde legacy.', [
                    'error' => $exception->getMessage(),
                    'filters' => $filters,
                ]);
            }
        }

        return [
            'products' => $result['products'],
            'total'    => $result['total'],
        ];
    }

    private function runFilteredQueryByConnection(
        ?string $connection,
        array $filters,
        int $limit,
        int $offset,
        ?string $userId,
    ): array {
        $db = $connection ? DB::connection($connection) : DB::connection();
        $searchOperator = $db->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = $db->table('products as p')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                    ->where('pi.is_primary', '=', true);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin($db->raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE is_approved = true GROUP BY product_id) as pr'), 'p.id', '=', 'pr.product_id')
            ->where('p.is_active', true)
            ->select([
                'p.*',
                'pi.image_path as primary_image',
                'c.name as category_name',
                $db->raw('COALESCE(pr.avg_rating, 0) as avg_rating'),
                $db->raw('COALESCE(pr.review_count, 0) as review_count'),
            ]);

        if ($userId) {
            $query->leftJoin('wishlist as w', function ($join) use ($userId) {
                $join->on('p.id', '=', 'w.product_id')
                    ->where('w.user_id', '=', $userId);
            });
            $query->addSelect($db->raw('CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END as is_favorite'));
        } else {
            $query->addSelect($db->raw('0 as is_favorite'));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search, $searchOperator) {
                $q->where('p.name', $searchOperator, "%{$search}%")
                    ->orWhere('p.description', $searchOperator, "%{$search}%")
                    ->orWhere('c.name', $searchOperator, "%{$search}%");
            });
        }

        if (!empty($filters['category'])) {
            $query->where('p.category_id', $filters['category']);
        }

        if (!empty($filters['gender'])) {
            $genderCriteria = $this->resolveGenderSearchCriteria((string) $filters['gender']);
            $query->where(function ($genderQuery) use ($genderCriteria, $searchOperator) {
                foreach ($genderCriteria as $criterion) {
                    $genderQuery->orWhere('p.gender', $searchOperator, $criterion);
                }
            });
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $query->where('p.price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $query->where('p.price', '<=', $filters['max_price']);
        }

        if (!empty($filters['offers'])) {
            $query->whereNotNull('p.compare_price')
                ->whereColumn('p.price', '<', 'p.compare_price');
        }

        if (!empty($filters['collection'])) {
            $query->where('p.collection_id', $filters['collection']);
        }

        $totalQuery = clone $query;
        $total = $totalQuery->count('p.id');

        $sortBy = $filters['sort'] ?? 'newest';
        match ($sortBy) {
            'popular' => $query->orderByDesc('pr.review_count'),
            'price_asc' => $query->orderBy('p.price'),
            'price_desc' => $query->orderByDesc('p.price'),
            'name_asc' => $query->orderBy('p.name'),
            'name_desc' => $query->orderByDesc('p.name'),
            default => $query->orderByDesc('p.is_featured')->orderByDesc('p.created_at'),
        };

        $products = $query->offset($offset)->limit($limit)->get()->toArray();

        return [
            'products' => array_map(fn($product) => (array) $product, $products),
            'total' => (int) $total,
        ];
    }

    /**
     * Normaliza filtros de género para soportar variantes con acentos/plurales y valores mojibake heredados.
     */
    private function resolveGenderSearchCriteria(string $rawGender): array
    {
        $normalized = mb_strtolower(trim($rawGender));
        $genderKey = Str::ascii($normalized);

        $criteria = match ($genderKey) {
            // Patrones tolerantes para cubrir nina/nino incluso cuando llegan con bytes degradados.
            'nina', 'ninas' => ['%ni%a%', '%nina%', '%niña%', '%niÃ±a%', '%niñas%', '%niÃ±as%'],
            'nino', 'ninos' => ['%ni%o%', '%nino%', '%niño%', '%niÃ±o%', '%niños%', '%niÃ±os%'],
            'bebe', 'bebes' => ['%be%b%', '%bebe%', '%bebé%', '%bebes%', '%bebés%', '%bebÃ©%', '%bebÃ©s%'],
            default => ["%{$normalized}%", '%' . Str::ascii($normalized) . '%'],
        };

        return array_values(array_unique(array_filter($criteria, static fn ($value) => trim((string) $value) !== '')));
    }

    /**
     * Get a single product by its slug.
     *
     * Migrated from getProductBySlug() in product-functions.php
     */
    public function findBySlug(string $slug): ?object
    {
        return DB::table('products as p')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                     ->where('pi.is_primary', '=', true);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('collections as col', 'p.collection_id', '=', 'col.id')
            ->where('p.slug', $slug)
            ->where('p.is_active', true)
            ->select([
                'p.*',
                'pi.image_path as primary_image',
                'c.name as category_name',
                'c.slug as category_slug',
                'col.name as collection_name',
                'col.slug as collection_slug',
            ])
            ->first();
    }

    /**
     * Get product variants (colors -> sizes -> images).
     *
     * Migrated from getProductVariants() in product-functions.php
     */
    public function getVariants(int $productId): array
    {
        $colorVariants = DB::table('product_color_variants as pcv')
            ->leftJoin('colors as c', 'pcv.color_id', '=', 'c.id')
            ->where('pcv.product_id', $productId)
            ->orderByDesc('pcv.is_default')
            ->select([
                'pcv.id as color_variant_id',
                'pcv.product_id',
                'pcv.color_id',
                'pcv.is_default',
                'c.name as color_name',
                'c.hex_code as color_hex',
            ])
            ->get();

        $variantsByColor = [];

        foreach ($colorVariants as $cv) {
            $sizeVariants = DB::table('product_size_variants as psv')
                ->leftJoin('sizes as s', 'psv.size_id', '=', 's.id')
                ->where('psv.color_variant_id', $cv->color_variant_id)
                ->where('psv.is_active', true)
                ->orderBy('s.id')
                ->select([
                    'psv.id', 'psv.color_variant_id', 'psv.size_id',
                    'psv.sku', 'psv.barcode', 'psv.price', 'psv.compare_price',
                    'psv.quantity', 'psv.is_active', 's.name as size_name',
                ])
                ->get();

            $images = DB::table('variant_images')
                ->where('color_variant_id', $cv->color_variant_id)
                ->where('product_id', $productId)
                ->orderByDesc('is_primary')
                ->orderBy('order')
                ->select(['image_path', 'alt_text', 'is_primary'])
                ->get()
                ->toArray();

            $sizes = [];
            foreach ($sizeVariants as $sv) {
                $effectiveQuantity = $this->resolveRealtimeAvailableStock(
                    (int) $sv->id,
                    (int) ($sv->quantity ?? 0),
                );

                $sizes[$sv->size_id] = [
                    'size_id'       => $sv->size_id,
                    'size_name'     => $sv->size_name,
                    'variant_id'    => $sv->id,
                    'sku'           => $sv->sku,
                    'price'         => $sv->price,
                    'compare_price' => $sv->compare_price,
                    'quantity'      => $effectiveQuantity,
                ];
            }

            $variantsByColor[$cv->color_id] = [
                'color_variant_id' => $cv->color_variant_id,
                'color_id'         => $cv->color_id,
                'color_name'       => $cv->color_name,
                'color_hex'        => $cv->color_hex,
                'sizes'            => $sizes,
                'images'           => array_map(fn($i) => (array) $i, $images),
            ];
        }

        return $variantsByColor;
    }

    /**
     * Get additional (non-primary) images.
     *
     * Migrated from getAdditionalImages() in product-functions.php
     */
    public function getAdditionalImages(int $productId): array
    {
        return DB::table('product_images')
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->whereNull('is_primary')
                  ->orWhere('is_primary', false);
            })
            ->orderBy('order')
            ->select(['image_path', 'alt_text'])
            ->get()
            ->map(fn($i) => (array) $i)
            ->toArray();
    }

    /**
     * Get related products.
     *
     * Migrated from getRelatedProducts() in product-functions.php
     */
    public function getRelated(int $productId, int $categoryId, int $limit = 4): array
    {
        return DB::table('products as p')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                     ->where('pi.is_primary', '=', true);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE is_approved = true GROUP BY product_id) as pr'), 'p.id', '=', 'pr.product_id')
            ->where('p.category_id', $categoryId)
            ->where('p.id', '!=', $productId)
            ->where('p.is_active', true)
            ->orderByDesc('p.is_featured')
            ->inRandomOrder()
            ->limit($limit)
            ->select([
                'p.id', 'p.name', 'p.slug', 'p.price', 'p.compare_price', 'p.is_featured',
                'pi.image_path',
                'c.name as category_name',
                DB::raw('COALESCE(pr.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(pr.review_count, 0) as review_count'),
            ])
            ->get()
            ->map(fn($p) => (array) $p)
            ->toArray();
    }

    /**
     * Get reviews with stats.
     *
     * Migrated from getProductReviews() in product-functions.php
     */
    public function getReviews(int $productId, ?string $userId = null): array
    {
        $query = DB::table('product_reviews as pr')
            ->where('pr.product_id', $productId)
            ->where('pr.is_approved', true)
            ->select([
                'pr.*',
                DB::raw('(SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = pr.id AND rv.is_helpful = true) as helpful_count'),
            ]);

        if ($userId) {
            $query->selectRaw(
                '(SELECT rv.is_helpful FROM review_votes rv WHERE rv.review_id = pr.id AND rv.user_id = ? LIMIT 1) as user_has_voted',
                [$userId],
            );
        } else {
            $query->addSelect(DB::raw('0 as user_has_voted'));
        }

        $reviews = $query
            ->orderByDesc('pr.is_verified')
            ->orderByDesc('helpful_count')
            ->orderByDesc('pr.created_at')
            ->limit(10)
            ->get()
            ->map(function ($review) {
                $item = (array) $review;
                $item['user_name'] = $this->resolveUserName(
                    null,
                    $item['user_id'] ?? null,
                );
                $item['user_image'] = $this->normalizeUserImagePath(null);
                $item['helpful_count'] = (int) ($item['helpful_count'] ?? 0);
                $item['user_has_voted'] = (int) ($item['user_has_voted'] ?? 0);
                return $item;
            })
            ->toArray();

        // Estadisticas para la seccion de barras de calificacion.
        $stats = DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('is_approved', true)
            ->selectRaw('COUNT(*) as total_reviews, AVG(rating) as average_rating')
            ->selectRaw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star')
            ->selectRaw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star')
            ->selectRaw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star')
            ->selectRaw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star')
            ->selectRaw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star')
            ->first();

        $stats = (array) ($stats ?: [
            'total_reviews' => 0,
            'average_rating' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0,
        ]);

        $totalReviews = (int) ($stats['total_reviews'] ?? 0);
        $stats['five_star_percent'] = $totalReviews > 0 ? (int) round(((int) ($stats['five_star'] ?? 0) / $totalReviews) * 100) : 0;
        $stats['four_star_percent'] = $totalReviews > 0 ? (int) round(((int) ($stats['four_star'] ?? 0) / $totalReviews) * 100) : 0;
        $stats['three_star_percent'] = $totalReviews > 0 ? (int) round(((int) ($stats['three_star'] ?? 0) / $totalReviews) * 100) : 0;
        $stats['two_star_percent'] = $totalReviews > 0 ? (int) round(((int) ($stats['two_star'] ?? 0) / $totalReviews) * 100) : 0;
        $stats['one_star_percent'] = $totalReviews > 0 ? (int) round(((int) ($stats['one_star'] ?? 0) / $totalReviews) * 100) : 0;

        $userHasReview = false;
        if ($userId) {
            $userHasReview = DB::table('product_reviews')
                ->where('product_id', $productId)
                ->where('user_id', $userId)
                ->exists();
        }

        return [
            'reviews' => $reviews,
            'stats' => $stats,
            'user_has_review' => $userHasReview,
        ];
    }

    /**
     * Get questions and answers.
     *
     * Migrated from getProductQuestions() in product-functions.php
     */
    public function getQuestions(int $productId, int $limit = 5, ?string $userId = null): array
    {
        $questions = DB::table('product_questions as pq')
            ->where('pq.product_id', $productId)
            ->orderByDesc('pq.created_at')
            ->limit($limit)
            ->select([
                'pq.*',
                DB::raw('(SELECT COUNT(*) FROM question_answers qa WHERE qa.question_id = pq.id) as answer_count'),
            ])
            ->get()
            ->map(function ($question) {
                $item = (array) $question;
                $item['user_name'] = $this->resolveUserName(
                    null,
                    $item['user_id'] ?? null,
                );
                $item['user_image'] = $this->normalizeUserImagePath(null);
                return $item;
            })
            ->toArray();

        foreach ($questions as &$question) {
            $question['answers'] = DB::table('question_answers as qa')
                ->where('qa.question_id', $question['id'])
                ->orderByDesc('qa.is_seller')
                ->orderBy('qa.created_at')
                ->select(['qa.*'])
                ->get()
                ->map(function ($answer) {
                    $item = (array) $answer;
                    $item['user_name'] = $this->resolveUserName(
                        null,
                        $item['user_id'] ?? null,
                    );
                    $item['user_image'] = $this->normalizeUserImagePath(null);
                    return $item;
                })
                ->toArray();
        }

        return $questions;
    }

    /**
     * Normaliza la ruta del avatar para mantener compatibilidad con legacy.
     */
    private function resolveRealtimeAvailableStock(int $sizeVariantId, int $fallbackQuantity): int
    {
        $safeFallback = max(0, $fallbackQuantity);

        try {
            // Redis mantiene stock en tiempo real incluyendo reservas temporales.
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
            // Si Redis no responde, se conserva el stock de base de datos.
        }

        return $safeFallback;
    }

    /**
     * Normaliza la ruta del avatar para mantener compatibilidad con legacy.
     */
    private function normalizeUserImagePath(?string $path): string
    {
        $cleanPath = trim((string) $path);

        if ($cleanPath === '') {
            return 'images/default-avatar.png';
        }

        if (str_contains($cleanPath, '/')) {
            return ltrim(str_replace('\\', '/', $cleanPath), '/');
        }

        return 'uploads/users/' . $cleanPath;
    }

    /**
     * Retorna el nombre mostrado del usuario con fallback consistente.
     */
    private function resolveUserName(?string $name, ?string $userId = null): string
    {
        $cleanName = trim((string) $name);
        if ($cleanName !== '') {
            return $cleanName;
        }

        $cleanUserId = trim((string) $userId);
        if ($cleanUserId === '') {
            return 'Usuario';
        }

        return 'Usuario ' . $cleanUserId;
    }
}


