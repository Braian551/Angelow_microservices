<?php

namespace App\Providers;

// Comentario de mantenimiento: Este proveedor registra dependencias y ajustes de arranque propios del servicio.

use Illuminate\Support\ServiceProvider;

/**
 * Configura servicios y dependencias durante el arranque de la aplicación.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
