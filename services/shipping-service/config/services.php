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
