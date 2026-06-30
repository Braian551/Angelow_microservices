<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega índices y una función de mantenimiento para acelerar validación de descuentos.
     */
    public function up(): void
    {
        // Los objetos de función usan PostgreSQL; en SQLite se omiten para no afectar pruebas.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // La función adapta CleanExpiredDiscounts del dump completo con semántica PostgreSQL.
        DB::unprepared(<<<'SQL'
CREATE INDEX IF NOT EXISTS idx_discount_codes_code_lower
    ON discount_codes (LOWER(code));

CREATE INDEX IF NOT EXISTS idx_discount_codes_active_dates
    ON discount_codes (is_active, start_date, end_date, used_count);

CREATE INDEX IF NOT EXISTS idx_discount_code_usage_code_user
    ON discount_code_usage (discount_code_id, user_id);

CREATE INDEX IF NOT EXISTS idx_bulk_discount_rules_active_range
    ON bulk_discount_rules (is_active, min_quantity, max_quantity, discount_percentage);

CREATE OR REPLACE FUNCTION discount_cleanup_expired_codes()
RETURNS TABLE (
    deactivated_codes INTEGER,
    purged_applied_discounts INTEGER
)
LANGUAGE plpgsql
AS $$
BEGIN
    WITH updated_codes AS (
        UPDATE discount_codes
        SET is_active = FALSE,
            updated_at = CURRENT_TIMESTAMP
        WHERE is_active = TRUE
          AND end_date IS NOT NULL
          AND end_date < CURRENT_TIMESTAMP
        RETURNING 1
    )
    SELECT COUNT(*)::INTEGER INTO deactivated_codes
    FROM updated_codes;

    WITH deleted_applied AS (
        DELETE FROM user_applied_discounts
        WHERE expires_at < CURRENT_TIMESTAMP - INTERVAL '2 months'
        RETURNING 1
    )
    SELECT COUNT(*)::INTEGER INTO purged_applied_discounts
    FROM deleted_applied;

    RETURN NEXT;
END;
$$;
SQL);
    }

    /**
     * Retira los objetos de rendimiento sin eliminar información de descuentos.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP FUNCTION IF EXISTS discount_cleanup_expired_codes();

DROP INDEX IF EXISTS idx_bulk_discount_rules_active_range;
DROP INDEX IF EXISTS idx_discount_code_usage_code_user;
DROP INDEX IF EXISTS idx_discount_codes_active_dates;
DROP INDEX IF EXISTS idx_discount_codes_code_lower;
SQL);
    }
};
