<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios principal de la aplicación.
 * Se usa para registrar bindings en el contenedor y ejecutar
 * lógica de arranque común a todo el servicio de notificaciones.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de Laravel.
     * Actualmente sin bindings adicionales.
     */
    public function register(): void
    {
        //
    }

    /**
     * Ejecuta lógica al arrancar la aplicación.
     * Actualmente sin configuraciones de bootstrap adicionales.
     */
    public function boot(): void
    {
        //
    }
}
