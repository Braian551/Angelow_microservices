<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para el registro de repositorios.
 *
 * Aquí se registran las vinculaciones entre interfaces de repositorio
 * y sus implementaciones concretas.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra las vinculaciones de repositorios en el contenedor.
     */
    public function register(): void
    {
        //
    }

    /**
     * Realiza configuraciones adicionales después de registrar los servicios.
     */
    public function boot(): void
    {
        //
    }
}
