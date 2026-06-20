<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Punto de entrada HTTP del servicio de autenticación
|--------------------------------------------------------------------------
|
| Todas las solicitudes web/API ingresan por aquí. Laravel se encarga
| de enrutar cada petición al controlador correspondiente.
|
| Flujo:
|   1. Marca el inicio de ejecución (LARAVEL_START).
|   2. Verifica si la app está en modo mantenimiento.
|   3. Carga el autoloader de Composer.
|   4. Bootstrapea la aplicación Laravel desde bootstrap/app.php.
|   5. Maneja la request entrante.
|
*/

define('LARAVEL_START', microtime(true));

// Verifica si la aplicación está en modo mantenimiento
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registra el autoloader de Composer
require __DIR__.'/../vendor/autoload.php';

// Bootstrapea Laravel y maneja la petición
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
