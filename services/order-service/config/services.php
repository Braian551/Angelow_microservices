<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'notifications' => [
        'base_url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service:8000/api'),
        'order_type_id' => (int) env('NOTIFICATION_ORDER_TYPE_ID', 1),
    ],

    'frontend' => [
        'store_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'catalog' => [
        'base_url' => env('CATALOG_SERVICE_URL', 'http://catalog-service:8000/api'),
        'variant_path' => env('CATALOG_VARIANT_PATH', '/internal/variants'),
        'inventory_commit_path' => env('CATALOG_INVENTORY_COMMIT_PATH', '/internal/inventory/commit'),
    ],

    'refunds' => [
        'team_email' => env('ORDER_REFUND_TEAM_EMAIL'),
    ],

    'stock_reservations' => [
        'ttl_seconds' => (int) env('ORDER_STOCK_RESERVATION_TTL', 7200),
        'lock_seconds' => (int) env('ORDER_STOCK_LOCK_SECONDS', 10),
        'reconciliation_batch_size' => (int) env('ORDER_STOCK_RECONCILIATION_BATCH_SIZE', 200),
        'extend_on_confirmation_seconds' => (int) env('ORDER_STOCK_RESERVATION_EXTEND_ON_CONFIRMATION', 1800),
        'ws_channel' => env('ORDER_STOCK_WS_CHANNEL', 'ws:orders:stock'),
    ],

];
