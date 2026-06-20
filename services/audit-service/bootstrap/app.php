<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| Bootstrap de la aplicación Laravel (audit-service)
|--------------------------------------------------------------------------
|
| Configuración central del servicio: rutas web, API, comandos de
| consola y health check. Se definen aquí los archivos de rutas
| y se dejan preparados los hooks para middleware y manejo de
| excepciones personalizados del audit-service.
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
        // Aquí se pueden agregar middlewares globales o por grupo
        // específicos para el servicio de auditoría.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejo centralizado de excepciones; se puede personalizar
        // para devolver respuestas JSON consistentes en errores de API.
    })->create();
