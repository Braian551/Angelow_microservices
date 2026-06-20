<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\QueryBuilderUserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para repositorios del auth-service.
 *
 * Vincula las interfaces de repositorio con sus implementaciones
 * concretas. Esto permite inyección de dependencias limpia y
 * facilita el testing al poder intercambiar implementaciones.
 *
 * Bindings registrados:
 *   UserRepositoryInterface::class => QueryBuilderUserRepository::class
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Todos los bindings de repositorios.
     * Se registran automáticamente por Laravel al declararlos en $bindings.
     */
    public array $bindings = [
        UserRepositoryInterface::class => QueryBuilderUserRepository::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
