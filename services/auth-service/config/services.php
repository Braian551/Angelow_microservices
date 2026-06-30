<?php

/*
|--------------------------------------------------------------------------
| Servicios de terceros y configuración del dominio (auth-service)
|--------------------------------------------------------------------------
|
| Agrupa las credenciales de servicios externos que consume el
| auth-service: Firebase (verificación de token Google), PHPMailer
| (envío SMTP), y la configuración interna del servicio.
|
| Incluye también parámetros del flujo de recuperación de contraseña
| como TTL del código, cooldown de reenvío y URL del frontend.
|
| Ver PasswordRecoveryService y AuthService para el uso en runtime.
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

    'firebase' => [
        'web_api_key' => env('FIREBASE_WEB_API_KEY', 'AIzaSyBQMRz1TvRiQAYt_PlimHGZHpuP-NSJt5k'),
    ],

    'turnstile' => [
        'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
        'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
    ],

    'login_protection' => [
        'captcha_after_attempts' => env('AUTH_LOGIN_CAPTCHA_AFTER_ATTEMPTS', 3),
        'temp_block_after_attempts' => env('AUTH_LOGIN_TEMP_BLOCK_AFTER_ATTEMPTS', 8),
        'temp_block_minutes' => env('AUTH_LOGIN_TEMP_BLOCK_MINUTES', 15),
    ],

    'internal' => [
        'api_token' => env('INTERNAL_API_TOKEN', ''),
    ],

    'password_recovery' => [
        'code_ttl' => env('PASSWORD_RECOVERY_CODE_TTL', 900),
        'resend_cooldown' => env('PASSWORD_RECOVERY_RESEND_COOLDOWN', 60),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'registration_verification' => [
        'code_ttl' => env('REGISTRATION_VERIFICATION_CODE_TTL', 900),
        'resend_cooldown' => env('REGISTRATION_VERIFICATION_RESEND_COOLDOWN', 60),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'phpmailer' => [
        'host' => env('PHPMAILER_HOST', 'smtp.gmail.com'),
        'port' => env('PHPMAILER_PORT', 587),
        'username' => env('PHPMAILER_USERNAME', ''),
        'password' => env('PHPMAILER_PASSWORD', ''),
        'encryption' => env('PHPMAILER_ENCRYPTION', 'tls'),
        'from_email' => env('PHPMAILER_FROM_EMAIL', 'seguridad@angelow.com'),
        'from_name' => env('PHPMAILER_FROM_NAME', 'Seguridad Angelow'),
    ],

];
