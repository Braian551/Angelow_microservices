<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crea objetos de lectura para acelerar catálogo sin cambiar contratos de API.
     */
    public function up(): void
    {
        // SQLite se mantiene sin objetos PostgreSQL para que las pruebas locales sigan portables.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // La vista reutiliza la lógica del procedimiento GetFilteredProducts del dump completo,
        // pero la adapta a PostgreSQL y deja el Query Builder actual como respaldo en la aplicación.
        DB::unprepared(<<<'SQL'
CREATE INDEX IF NOT EXISTS idx_products_active_filters
    ON products (is_active, category_id, gender, collection_id, is_featured, created_at);

CREATE INDEX IF NOT EXISTS idx_products_active_price
    ON products (is_active, price);

CREATE INDEX IF NOT EXISTS idx_product_images_primary_order
    ON product_images (product_id, is_primary DESC, "order", id);

CREATE INDEX IF NOT EXISTS idx_product_reviews_product_approved_rating
    ON product_reviews (product_id, is_approved, rating);

CREATE INDEX IF NOT EXISTS idx_product_questions_product_created
    ON product_questions (product_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_search_history_user_term_created
    ON search_history (user_id, search_term, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_popular_searches_term_count
    ON popular_searches (search_term, search_count DESC);

CREATE OR REPLACE VIEW catalog_product_listing_view AS
SELECT
    p.id,
    p.name,
    p.slug,
    p.description,
    p.brand,
    p.gender,
    p.collection,
    p.material,
    p.care_instructions,
    p.compare_price,
    p.price,
    p.category_id,
    c.name AS category_name,
    c.slug AS category_slug,
    p.collection_id,
    col.name AS collection_name,
    col.slug AS collection_slug,
    p.is_featured,
    p.is_active,
    p.created_at,
    p.updated_at,
    p.trial554,
    COALESCE(primary_image.image_path, 'uploads/products/default-product.jpg') AS primary_image,
    COALESCE(price_stats.min_price, p.price, 0)::numeric(10, 2) AS min_price,
    COALESCE(price_stats.max_price, p.price, 0)::numeric(10, 2) AS max_price,
    COALESCE(price_stats.available_stock, 0)::integer AS available_stock,
    COALESCE(review_stats.avg_rating, 0)::numeric(4, 2) AS avg_rating,
    COALESCE(review_stats.review_count, 0)::integer AS review_count
FROM products p
LEFT JOIN categories c ON c.id = p.category_id
LEFT JOIN collections col ON col.id = p.collection_id
LEFT JOIN LATERAL (
    SELECT pi.image_path
    FROM product_images pi
    WHERE pi.product_id = p.id
    ORDER BY pi.is_primary DESC, pi."order" ASC, pi.id ASC
    LIMIT 1
) AS primary_image ON TRUE
LEFT JOIN LATERAL (
    SELECT
        MIN(psv.price) AS min_price,
        MAX(psv.price) AS max_price,
        SUM(CASE WHEN psv.is_active THEN psv.quantity ELSE 0 END) AS available_stock
    FROM product_color_variants pcv
    JOIN product_size_variants psv ON psv.color_variant_id = pcv.id
    WHERE pcv.product_id = p.id
) AS price_stats ON TRUE
LEFT JOIN LATERAL (
    SELECT
        AVG(pr.rating) AS avg_rating,
        COUNT(*) AS review_count
    FROM product_reviews pr
    WHERE pr.product_id = p.id
      AND COALESCE(pr.is_approved, TRUE) = TRUE
) AS review_stats ON TRUE;

CREATE OR REPLACE FUNCTION catalog_search_products_and_terms(
    p_search_term TEXT,
    p_limit INTEGER DEFAULT 5
)
RETURNS TABLE (
    result_type TEXT,
    product_id INTEGER,
    name VARCHAR(255),
    slug VARCHAR(255),
    image_path VARCHAR(255),
    search_term_result VARCHAR(255),
    sort_rank INTEGER
)
LANGUAGE sql
STABLE
AS $$
    (
        SELECT
            'product'::TEXT AS result_type,
            p.id AS product_id,
            p.name,
            p.slug,
            p.primary_image::VARCHAR(255) AS image_path,
            NULL::VARCHAR(255) AS search_term_result,
            CASE
                WHEN p.name ILIKE p_search_term || '%' THEN 1
                WHEN p.name ILIKE '%' || p_search_term || '%' THEN 2
                ELSE 3
            END AS sort_rank
        FROM catalog_product_listing_view p
        WHERE p.is_active = TRUE
          AND (
              p.name ILIKE '%' || p_search_term || '%'
              OR p.description ILIKE '%' || p_search_term || '%'
              OR p.brand ILIKE '%' || p_search_term || '%'
          )
        ORDER BY sort_rank, p.name
        LIMIT GREATEST(1, LEAST(COALESCE(p_limit, 5), 20))
    )
    UNION ALL
    (
        SELECT
            'term'::TEXT AS result_type,
            NULL::INTEGER AS product_id,
            NULL::VARCHAR(255) AS name,
            NULL::VARCHAR(255) AS slug,
            NULL::VARCHAR(255) AS image_path,
            terms.name::VARCHAR(255) AS search_term_result,
            10 AS sort_rank
        FROM (
            SELECT DISTINCT p.name
            FROM catalog_product_listing_view p
            WHERE p.is_active = TRUE
              AND p.name ILIKE '%' || p_search_term || '%'
              AND p.name IS NOT NULL
              AND p.name <> ''
            ORDER BY p.name
            LIMIT 6
        ) AS terms
    );
$$;
SQL);
    }

    /**
     * Elimina objetos de rendimiento sin tocar datos de catálogo.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP FUNCTION IF EXISTS catalog_search_products_and_terms(TEXT, INTEGER);
DROP VIEW IF EXISTS catalog_product_listing_view;

DROP INDEX IF EXISTS idx_popular_searches_term_count;
DROP INDEX IF EXISTS idx_search_history_user_term_created;
DROP INDEX IF EXISTS idx_product_questions_product_created;
DROP INDEX IF EXISTS idx_product_reviews_product_approved_rating;
DROP INDEX IF EXISTS idx_product_images_primary_order;
DROP INDEX IF EXISTS idx_products_active_price;
DROP INDEX IF EXISTS idx_products_active_filters;
SQL);
    }
};
