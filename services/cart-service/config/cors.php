<?php

/*
|--------------------------------------------------------------------------
| Configuración CORS del cart-service
|--------------------------------------------------------------------------
|
| Permite peticiones cross-origin desde cualquier origen en desarrollo
| (modo API, accesible desde frontend SPA en puerto 5173).
| En producción, restringir allowed_origins a los dominios reales.
|
| Soporta todos los métodos y encabezados para las rutas /api/*.
|
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
