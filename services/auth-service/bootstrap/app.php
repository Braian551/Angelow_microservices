<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Bootstrap de la aplicación Laravel (auth-service)
|--------------------------------------------------------------------------
|
| Configuración central del servicio: rutas web, API, comandos de
| consola, health check, middleware global CORS y manejo de
| excepciones para respuestas JSON en endpoints de API.
|
| Middleware global: CorsMiddleware (encabezados CORS para frontend Vue).
| Manejo de excepciones: respuestas JSON automáticas para rutas /api/*.
|
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware CORS global para permitir peticiones desde el frontend Vue
        $middleware->append(\App\Http\Middleware\CorsMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Retorna JSON para errores de validación en rutas de API
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
