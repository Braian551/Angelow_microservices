<?php

/*
|--------------------------------------------------------------------------
| Servicios externos del cart-service
|--------------------------------------------------------------------------
|
| Configuración de servicios que consume el cart-service:
| - notifications: URL y token del notification-service para recordatorios
| - frontend: URL de la tienda para enlaces en mensajes de carrito
|
| El catalog-service se configura vía CATALOG_API_URL en .env
| y se usa directamente en CartService para consultar productos.
|
| @see CartService::catalogBaseUrl()
| @see CartService::dispatchAbandonedCartReminders()
|
*/

return [

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
        // URL base usada para disparar recordatorios de carritos abandonados.
        'base_url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service:8000/api'),
        // Token interno compartido para proteger llamadas entre microservicios.
        'internal_token' => env('AUTH_INTERNAL_TOKEN', env('INTERNAL_API_TOKEN', '')),
    ],

    'frontend' => [
        // URL pública de la tienda usada para construir enlaces de retorno al carrito.
        'store_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

];
