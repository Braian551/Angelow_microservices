<?php

namespace App\Repositories;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

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
        $query = DB::table('products as p')
            ->leftJoin('product_images as pi', function ($join) {
                $join->on('p.id', '=', 'pi.product_id')
                     ->where('pi.is_primary', '=', 1);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE is_approved = 1 GROUP BY product_id) as pr'), 'p.id', '=', 'pr.product_id')
            ->where('p.is_active', 1)
            ->select([
                'p.*',
                'pi.image_path as primary_image',
                'c.name as category_name',
                DB::raw('COALESCE(pr.avg_rating, 0) as avg_rating'),
                DB::raw('COALESCE(pr.review_count, 0) as review_count'),
            ]);

        // Join favorites if user provided
        if ($userId) {
            $query->leftJoin('wishlist as w', function ($join) use ($userId) {
                $join->on('p.id', '=', 'w.product_id')
                     ->where('w.user_id', '=', $userId);
            });
            $query->addSelect(DB::raw('IF(w.id IS NOT NULL, 1, 0) as is_favorite'));
        } else {
            $query->addSelect(DB::raw('0 as is_favorite'));
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('p.name', 'like', "%{$search}%")
                  ->orWhere('p.description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category'])) {
            $query->where('p.category_id', $filters['category']);
        }

        // Gender filter
        if (!empty($filters['gender'])) {
            $query->where('p.gender', $filters['gender']);
        }

        // Price range filter
        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $query->where('p.price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $query->where('p.price', '<=', $filters['max_price']);
        }

        // Offers only filter
        if (!empty($filters['offers'])) {
            $query->whereNotNull('p.compare_price')
                  ->whereColumn('p.price', '<', 'p.compare_price');
        }

        // Collection filter
        if (!empty($filters['collection'])) {
            $query->where('p.collection_id', $filters['collection']);
        }

        // Get total count before limit/offset
        $totalQuery = clone $query;
        $total = $totalQuery->count('p.id');

        // Sorting
        $sortBy = $filters['sort'] ?? 'newest';
        match ($sortBy) {
            'popular'    => $query->orderByDesc('pr.review_count'),
            'price_asc'  => $query->orderBy('p.price'),
            'price_desc' => $query->orderByDesc('p.price'),
            'name_asc'   => $query->orderBy('p.name'),
            'name_desc'  => $query->orderByDesc('p.name'),
            default      => $query->orderByDesc('p.created_at'),
        };

        $products = $query->offset($offset)->limit($limit)->get()->toArray();

        // Convert stdClass objects to arrays
        $products = array_map(fn($p) => (array) $p, $products);

        return [
            'products' => $products,
            'total'    => $total,
        ];
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
                     ->where('pi.is_primary', '=', 1);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('collections as col', 'p.collection_id', '=', 'col.id')
            ->where('p.slug', $slug)
            ->where('p.is_active', 1)
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
     * Get product variants (colors → sizes → images).
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
                ->where('psv.is_active', 1)
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
                $sizes[$sv->size_id] = [
                    'size_id'       => $sv->size_id,
                    'size_name'     => $sv->size_name,
                    'variant_id'    => $sv->id,
                    'sku'           => $sv->sku,
                    'price'         => $sv->price,
                    'compare_price' => $sv->compare_price,
                    'quantity'      => $sv->quantity,
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
                  ->orWhere('is_primary', 0);
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
                     ->where('pi.is_primary', '=', 1);
            })
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin(DB::raw('(SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE is_approved = 1 GROUP BY product_id) as pr'), 'p.id', '=', 'pr.product_id')
            ->where('p.category_id', $categoryId)
            ->where('p.id', '!=', $productId)
            ->where('p.is_active', 1)
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
            ->leftJoin('users as u', 'pr.user_id', '=', 'u.id')
            ->where('pr.product_id', $productId)
            ->where('pr.is_approved', 1)
            ->orderByDesc('pr.is_verified')
            ->orderByDesc('pr.created_at')
            ->limit(10)
            ->select([
                'pr.*',
                'u.name as user_name',
                'u.image as user_image',
            ]);

        $reviews = $query->get()->map(fn($r) => (array) $r)->toArray();

        // Stats
        $stats = DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('is_approved', 1)
            ->selectRaw('COUNT(*) as total_reviews, AVG(rating) as average_rating')
            ->selectRaw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star')
            ->selectRaw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star')
            ->selectRaw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star')
            ->selectRaw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star')
            ->selectRaw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star')
            ->first();

        $stats = (array) ($stats ?: [
            'total_reviews'  => 0,
            'average_rating' => 0,
            'five_star' => 0, 'four_star' => 0, 'three_star' => 0,
            'two_star' => 0, 'one_star' => 0,
        ]);

        return [
            'reviews' => $reviews,
            'stats'   => $stats,
        ];
    }

    /**
     * Get questions and answers.
     *
     * Migrated from getProductQuestions() in product-functions.php
     */
    public function getQuestions(int $productId, int $limit = 5): array
    {
        $questions = DB::table('product_questions as pq')
            ->leftJoin('users as u', 'pq.user_id', '=', 'u.id')
            ->where('pq.product_id', $productId)
            ->orderByDesc('pq.created_at')
            ->limit($limit)
            ->select([
                'pq.*',
                'u.name as user_name',
                'u.image as user_image',
            ])
            ->get()
            ->map(fn($q) => (array) $q)
            ->toArray();

        foreach ($questions as &$question) {
            $question['answers'] = DB::table('question_answers as qa')
                ->leftJoin('users as u', 'qa.user_id', '=', 'u.id')
                ->where('qa.question_id', $question['id'])
                ->orderByDesc('qa.is_seller')
                ->orderBy('qa.created_at')
                ->select(['qa.*', 'u.name as user_name', 'u.image as user_image'])
                ->get()
                ->map(fn($a) => (array) $a)
                ->toArray();
        }

        return $questions;
    }
}
