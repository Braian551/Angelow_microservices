<?php

return [
    'alert_emails' => env('INVENTORY_ALERT_EMAILS', ''),
    'send_initial_email' => filter_var(
        env('INVENTORY_ALERT_SEND_INITIAL_EMAIL', true),
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE,
    ) ?? true,
    'reminder_after_days' => max(1, (int) env('INVENTORY_ALERT_REMINDER_AFTER_DAYS', 3)),
    'reminder_every_days' => max(1, (int) env('INVENTORY_ALERT_REMINDER_EVERY_DAYS', 3)),
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),
    'inventory_admin_path' => env('INVENTORY_ALERT_ADMIN_PATH', '/admin/inventario'),
];