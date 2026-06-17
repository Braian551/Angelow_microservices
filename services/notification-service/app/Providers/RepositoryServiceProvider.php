<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para registro de repositorios.
 * Separado del AppServiceProvider para mantener la organización
 * cuando se agreguen patrones repositorio en el futuro.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra bindings de interfaces con sus implementaciones concretas.
     * Actualmente sin registros; preparado para crecimiento del servicio.
     */
    public function register(): void
    {
        //
    }

    /**
     * Ejecuta lógica de arranque para los repositorios registrados.
     * Actualmente sin implementación.
     */
    public function boot(): void
    {
        //
    }
}
