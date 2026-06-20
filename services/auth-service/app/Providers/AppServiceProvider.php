<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios principal del auth-service.
 *
 * Aquí se registran binding y bootstrapping general del contenedor
 * de Laravel. Actualmente no requiere lógica personalizada adicional
 * porque los bindings de repositorios están en RepositoryServiceProvider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de la aplicación.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrapping posterior al registro de todos los proveedores.
     */
    public function boot(): void
    {
        //
    }
}
