<?php

/*
|--------------------------------------------------------------------------
| Bootstrap del cart-service
|--------------------------------------------------------------------------
|
| Configura y retorna la instancia de la aplicación Laravel para el
| servicio de carrito. Define las rutas web, API y de consola, así
| como la configuración de middleware y manejo de excepciones.
|
| URL base: http://localhost:8003
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
