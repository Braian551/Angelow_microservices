<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para registro de repositorios del módulo de descuentos.
 * Separado del AppServiceProvider para mantener la organización
 * cuando se agreguen patrones repositorio en el futuro.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra bindings de interfaces con sus implementaciones concretas.
     * Actualmente sin registros; preparado para cuando se adopte el patrón repositorio.
     */
    public function register(): void
    {
        // Espacio reservado para $this->app->bind(Interface::class, Implementation::class);
    }

    /**
     * Ejecuta lógica de arranque para los repositorios registrados.
     * Actualmente sin implementación.
     */
    public function boot(): void
    {
        // Espacio reservado para inicializar repositorios o ejecutar migraciones internas.
    }
}
