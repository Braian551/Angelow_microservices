<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Punto de entrada HTTP para shipping-service
|--------------------------------------------------------------------------
|
| Este archivo es el front controller de todas las peticiones HTTP
| que llegan al microservicio de envíos. Su función es:
| 1. Registrar el tiempo de inicio de Laravel.
| 2. Verificar modo de mantenimiento.
| 3. Cargar el autoloader de Composer.
| 4. Inicializar la aplicación Laravel desde bootstrap/app.php.
| 5. Procesar la solicitud y devolver la respuesta.
|
*/

define('LARAVEL_START', microtime(true));

// Verifica si la aplicación está en modo mantenimiento
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registra el autoloader de Composer (carga de clases)
require __DIR__.'/../vendor/autoload.php';

// Inicializa Laravel y procesa la solicitud
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
