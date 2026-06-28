<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Inicialización de la aplicación Laravel para shipping-service.
 *
 * Configura:
 * - Rutas web (para vistas básicas)
 * - Rutas API (endpoints REST del microservicio)
 * - Comandos de consola (Artisan)
 * - Endpoint de health check (/up)
 *
 * Los middleware globales y el manejo de excepciones se dejan
 * con configuración predeterminada de Laravel. Los middleware
 * personalizados (como EnsureAdmin) se asignan en api.php.
 */
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
