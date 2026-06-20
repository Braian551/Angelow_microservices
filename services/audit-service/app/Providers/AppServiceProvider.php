<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios principal de la aplicación (audit-service).
 *
 * Aquí se registran binding y bootstrapping general del contenedor
 * de Laravel. Actualmente no requiere lógica personalizada, pero
 * se deja preparado para futuras necesidades del servicio.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra cualquier servicio en el contenedor de la aplicación.
     *
     * Este método se ejecuta antes de que cualquier proveedor
     *sea booteado. Es el lugar adecuado para binding de interfaces
     *o singletons que el audit-service pueda necesitar.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrapping: se ejecuta después de que todos los proveedores
     * han sido registrados. Ideal para configurar aspectos como
     * rutas, vistas o eventos globales del servicio.
     */
    public function boot(): void
    {
        //
    }
}
