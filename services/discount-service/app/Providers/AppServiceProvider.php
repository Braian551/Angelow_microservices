<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios principal de la aplicación de descuentos.
 * Se usa para registrar bindings en el contenedor y ejecutar
 * lógica de arranque común a todo el servicio.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de Laravel.
     * Actualmente sin bindings adicionales; preparado para futuras dependencias del módulo de descuentos.
     */
    public function register(): void
    {
        // Espacio reservado para registrar bindings, singletons o instancias en el contenedor.
    }

    /**
     * Ejecuta lógica al arrancar la aplicación.
     * Actualmente sin configuraciones de bootstrap adicionales.
     */
    public function boot(): void
    {
        // Espacio reservado para ejecutar lógica al iniciar el servicio de descuentos.
    }
}
