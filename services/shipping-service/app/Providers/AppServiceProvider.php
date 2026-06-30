<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios principal de shipping-service.
 *
 * Aquí se registran bindings, eventos y configuraciones globales
 * que aplican a todo el microservicio. Actualmente sin registros
 * adicionales, pero se mantiene como punto de extensión para
 * futuras necesidades (ej: registrar repositorios, observers,
 * o configuraciones de terceros).
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra cualquier servicio de la aplicación.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa cualquier servicio después de registrar todos los providers.
     */
    public function boot(): void
    {
        //
    }
}
