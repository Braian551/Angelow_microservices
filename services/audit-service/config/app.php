<?php

/*
|--------------------------------------------------------------------------
| Configuración general de la aplicación (audit-service)
|--------------------------------------------------------------------------
|
| Define nombre, entorno, zona horaria, locale y clave de cifrado
| del servicio de auditoría. Los valores se obtienen de .env y
| pueden sobrescribirse por entorno (local, staging, producción).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Nombre de la aplicación
    |--------------------------------------------------------------------------
    |
    | Nombre visible del servicio. Se usa en notificaciones y metadatos.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Entorno de ejecución
    |--------------------------------------------------------------------------
    |
    | Define si la app corre en local, staging, producción, etc.
    | Afecta el nivel de logging, caché y mensajes de error.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Modo debug
    |--------------------------------------------------------------------------
    |
    | En true muestra errores detallados con stack traces.
    | Deshabilitar en producción por seguridad.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL base de la aplicación
    |--------------------------------------------------------------------------
    |
    | Usada por Artisan para generar URLs correctas en comandos
    | y notificaciones.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Zona horaria
    |--------------------------------------------------------------------------
    |
    | Zona horaria por defecto para todas las funciones de fecha/hora
    | en PHP y Laravel. Se mantiene en UTC por compatibilidad distribuida.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Configuración regional (locale)
    |--------------------------------------------------------------------------
    |
    | Define el idioma de las traducciones y el generador de datos falsos.
    | Se usa español (es) para consistencia con el resto de Angelow.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Clave de cifrado
    |--------------------------------------------------------------------------
    |
    | Clave AES-256-CBC usada por Laravel para encriptar cookies,
    | sesiones y otros datos sensibles. Debe ser una cadena de 32
    | caracteres generada con `php artisan key:generate`.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo mantenimiento
    |--------------------------------------------------------------------------
    |
    | Controla cómo se gestiona el modo mantenimiento. El driver "file"
    | es suficiente para un solo servidor; "cache" permite coordinación
    | entre múltiples instancias.
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
