<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para repositorios del audit-service.
 *
 * Separado del AppServiceProvider para mantener la inyección
 * de dependencias de repositorios en un lugar dedicado.
 * Aquí se registrarían los bindings entre interfaces y sus
 * implementaciones concretas cuando el servicio necesite
 * abstracciones de almacenamiento de auditoría.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra bindings de repositorios en el contenedor.
     *
     * Actualmente vacío; se expandirá cuando se definan
     * interfaces Repository para las tablas de auditoría.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrapping posterior al registro de repositorios.
     *
     * Puede usarse para configurar observers, filtros globales
     * o dependencias adicionales de los repositorios.
     */
    public function boot(): void
    {
        //
    }
}
