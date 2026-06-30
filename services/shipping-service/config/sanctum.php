<?php

use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Configuración de Sanctum para shipping-service
|--------------------------------------------------------------------------
|
| NOTA: Actualmente shipping-service NO usa Sanctum como guard de
| autenticación principal. La autenticación se maneja mediante
| tokens JWT validados contra auth-service (ver middleware EnsureAdmin).
|
| Esta configuración se mantiene por compatibilidad con el scaffolding
| de Laravel, pero no afecta la operación del servicio.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Dominios con estado (stateful)
    |--------------------------------------------------------------------------
    |
    | Define qué dominios/hosts reciben cookies de autenticación stateful.
    | Incluye localhost y los puertos comunes de desarrollo.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
        // Sanctum::currentRequestHost(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Guards de Sanctum
    |--------------------------------------------------------------------------
    |
    | Guards que Sanctum verificará al autenticar una solicitud.
    | shipping-service usa 'web' como guard por defecto, pero
    | la autenticación real se delega a auth-service.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Minutos de expiración de tokens
    |--------------------------------------------------------------------------
    |
    | Tiempo de vida de los tokens emitidos por Sanctum (null = sin expiración).
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Prefijo de tokens
    |--------------------------------------------------------------------------
    |
    | Prefijo opcional para identificar tokens emitidos por esta aplicación
    | en escaneos de seguridad de plataformas como GitHub.
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Middleware de Sanctum
    |--------------------------------------------------------------------------
    |
    | Middleware usado durante la autenticación stateful con Sanctum.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
