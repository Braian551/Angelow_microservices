<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega objetos de lectura e índices para acelerar bandeja y cola de notificaciones.
     */
    public function up(): void
    {
        // Se limita a PostgreSQL para no romper pruebas con SQLite.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // No se crea trigger de cola porque NotificationDispatchService ya inserta la cola una sola vez.
        DB::unprepared(<<<'SQL'
CREATE INDEX IF NOT EXISTS idx_notifications_user_read_created
    ON notifications (user_id, is_read, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_notifications_expires_at
    ON notifications (expires_at);

CREATE INDEX IF NOT EXISTS idx_notification_queue_status_scheduled
    ON notification_queue (status, scheduled_at, attempts);

CREATE INDEX IF NOT EXISTS idx_admin_notification_dismissals_admin_key
    ON admin_notification_dismissals (admin_id, notification_key);

CREATE INDEX IF NOT EXISTS idx_announcements_active_window
    ON announcements (is_active, priority DESC, start_date, end_date);

CREATE OR REPLACE VIEW notification_inbox_view AS
SELECT
    n.id,
    n.user_id,
    n.type_id,
    nt.name AS type_name,
    nt.description AS type_description,
    n.title,
    n.message,
    n.related_entity_type,
    n.related_entity_id,
    n.is_read,
    n.is_email_sent,
    n.is_sms_sent,
    n.is_push_sent,
    n.expires_at,
    n.created_at,
    n.read_at
FROM notifications n
LEFT JOIN notification_types nt ON nt.id = n.type_id
WHERE n.expires_at IS NULL
   OR n.expires_at > CURRENT_TIMESTAMP;
SQL);
    }

    /**
     * Retira objetos de rendimiento sin tocar notificaciones existentes.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP VIEW IF EXISTS notification_inbox_view;

DROP INDEX IF EXISTS idx_announcements_active_window;
DROP INDEX IF EXISTS idx_admin_notification_dismissals_admin_key;
DROP INDEX IF EXISTS idx_notification_queue_status_scheduled;
DROP INDEX IF EXISTS idx_notifications_expires_at;
DROP INDEX IF EXISTS idx_notifications_user_read_created;
SQL);
    }
};
