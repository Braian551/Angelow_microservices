<?php

namespace App\Providers;

// Comentario de mantenimiento: Este proveedor registra dependencias y ajustes de arranque propios del servicio.

use Illuminate\Support\ServiceProvider;

/**
 * Configura servicios y dependencias durante el arranque de la aplicación.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra bindings y servicios necesarios durante el arranque de Laravel.
     */
    public function register(): void
    {
        //
    }

    /**
     * Ejecuta ajustes de arranque cuando el framework ya resolvió sus servicios.
     */

    public function boot(): void
    {
        //
    }
}
