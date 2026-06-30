<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para repositorios de shipping-service.
 *
 * Destinado a registrar bindings de interfaces con implementaciones
 * concretas de repositorios cuando se decida adoptar el patrón Repository.
 * Actualmente el servicio trabaja directamente con modelos Eloquent,
 * pero este provider se mantiene como preparación para cuando se
 * necesite abstraer la capa de datos.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra bindings de repositorios en el contenedor.
     */
    public function register(): void
    {
        //
    }

    /**
     * Ejecuta lógica después de registrar todos los providers.
     */
    public function boot(): void
    {
        //
    }
}
