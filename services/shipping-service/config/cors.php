<?php

/*
|--------------------------------------------------------------------------
| Configuración CORS para shipping-service
|--------------------------------------------------------------------------
|
| Permite solicitudes cross-origin desde cualquier origen (el frontend SPA
| se sirve en un dominio/puerto diferente al de las APIs). La configuración
| es permisiva porque el gateway/api-gateway se encarga de restringir
| orígenes en producción. El microservicio acepta cualquier método,
| origen y cabecera para las rutas /api/*.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Rutas donde aplicar CORS
    |--------------------------------------------------------------------------
    |
    | Se aplica a todas las rutas de API y al endpoint CSRF de Sanctum
    | (aunque Sanctum no se usa activamente como guard, se mantiene
    | para compatibilidad con futuras necesidades de SPA).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /** Métodos HTTP permitidos (todos) */
    'allowed_methods' => ['*'],

    /** Orígenes permitidos (todos; el gateway restringe en producción) */
    'allowed_origins' => ['*'],

    /** Patrones de orígenes permitidos (ninguno adicional) */
    'allowed_origins_patterns' => [],

    /** Cabeceras HTTP permitidas (todas) */
    'allowed_headers' => ['*'],

    /** Cabeceras expuestas al cliente (ninguna adicional) */
    'exposed_headers' => [],

    /** Tiempo máximo de cacheo de preflight (0 = sin cacheo) */
    'max_age' => 0,

    /** Soporte de credenciales (cookies, cabeceras de autenticación) */
    'supports_credentials' => false,

];
