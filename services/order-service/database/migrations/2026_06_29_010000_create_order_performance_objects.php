<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega objetos de lectura e índices para acelerar reportes e historial de pedidos.
     */
    public function up(): void
    {
        // PostgreSQL soporta vistas e índices compuestos usados por producción.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Se evitan triggers de historial porque los controladores ya registran cambios explícitamente.
        DB::unprepared(<<<'SQL'
CREATE INDEX IF NOT EXISTS idx_orders_user_created
    ON orders (user_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_orders_admin_reports
    ON orders (created_at DESC, status, payment_status);

CREATE INDEX IF NOT EXISTS idx_orders_payment_status_created
    ON orders (payment_status, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_order_items_product_created
    ON order_items (product_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_order_status_history_order_created
    ON order_status_history (order_id, created_at DESC, id DESC);

CREATE INDEX IF NOT EXISTS idx_order_views_user_viewed
    ON order_views (user_id, viewed_at DESC);

CREATE INDEX IF NOT EXISTS idx_order_refund_requests_status_requested
    ON order_refund_requests (status, requested_at DESC);

CREATE OR REPLACE VIEW order_history_view AS
SELECT
    osh.id,
    osh.order_id,
    o.order_number,
    o.user_id AS order_user_id,
    o.status AS order_status,
    o.payment_status AS order_payment_status,
    osh.changed_by,
    osh.changed_by_name,
    osh.change_type,
    osh.field_changed,
    osh.old_value,
    osh.new_value,
    osh.description,
    osh.ip_address,
    osh.user_agent,
    osh.created_at
FROM order_status_history osh
JOIN orders o ON o.id = osh.order_id;

CREATE OR REPLACE VIEW order_sales_daily_view AS
SELECT
    DATE_TRUNC('day', created_at)::DATE AS sales_date,
    status,
    payment_status,
    COUNT(*)::INTEGER AS orders_count,
    COALESCE(SUM(subtotal), 0)::numeric(12, 2) AS subtotal,
    COALESCE(SUM(shipping_cost), 0)::numeric(12, 2) AS shipping_total,
    COALESCE(SUM(discount_amount), 0)::numeric(12, 2) AS discount_total,
    COALESCE(SUM(total), 0)::numeric(12, 2) AS sales_total
FROM orders
GROUP BY DATE_TRUNC('day', created_at)::DATE, status, payment_status;
SQL);
    }

    /**
     * Retira vistas e índices agregados para rendimiento.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP VIEW IF EXISTS order_sales_daily_view;
DROP VIEW IF EXISTS order_history_view;

DROP INDEX IF EXISTS idx_order_views_user_viewed;
DROP INDEX IF EXISTS idx_order_refund_requests_status_requested;
DROP INDEX IF EXISTS idx_order_status_history_order_created;
DROP INDEX IF EXISTS idx_order_items_product_created;
DROP INDEX IF EXISTS idx_orders_payment_status_created;
DROP INDEX IF EXISTS idx_orders_admin_reports;
DROP INDEX IF EXISTS idx_orders_user_created;
SQL);
    }
};
