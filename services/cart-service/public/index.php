<?php

/*
|--------------------------------------------------------------------------
| Front controller del cart-service
|--------------------------------------------------------------------------
|
| Punto de entrada para todas las peticiones HTTP al servicio.
| Verifica modo mantenimiento, carga el autoloader de Composer
| y delega el manejo de la petición a la aplicación Laravel.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Verifica si la aplicación está en modo mantenimiento...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Carga el autoloader de Composer...
require __DIR__.'/../vendor/autoload.php';

// Inicializa Laravel y maneja la petición...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
