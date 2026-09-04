<?php

/*
|--------------------------------------------------------------------------
| Servicios de terceros para shipping-service
|--------------------------------------------------------------------------
|
| Almacena las credenciales de servicios externos como Mailgun,
| Postmark, AWS y Slack. Estos servicios se usan para envío de
| correos, notificaciones y almacenamiento en la nube.
|
| Actualmente shipping-service no envía correos directamente,
| pero la configuración se mantiene para compatibilidad con
| el framework Laravel y futuras necesidades (ej: notificaciones
| de estado de envío al usuario).
|
*/

return [

    'auth' => [
        'base_url' => env('AUTH_SERVICE_URL', 'http://auth-service:8000/api'),
    ],

    'orders' => [
        'base_url' => env('ORDER_SERVICE_URL', 'http://order-service:8000/api'),
    ],

    'notifications' => [
        'base_url' => env('NOTIFICATION_SERVICE_URL', 'http://notification-service:8000/api'),
    ],

    'mapbox' => [
        'access_token' => env('MAPBOX_ACCESS_TOKEN', ''),
    ],

    'internal_token' => env('AUTH_INTERNAL_TOKEN', ''),

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

    /** Slack para notificaciones de logs de error */
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
