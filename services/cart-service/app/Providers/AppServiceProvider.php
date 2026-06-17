<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios principal del cart-service.
 * Registra servicios y configuración global de la aplicación.
 * Los bindings de repositorios se delegan a RepositoryServiceProvider.
 *
 * @see RepositoryServiceProvider
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de Laravel.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa servicios después de que todos los proveedores están registrados.
     */
    public function boot(): void
    {
        //
    }
}
