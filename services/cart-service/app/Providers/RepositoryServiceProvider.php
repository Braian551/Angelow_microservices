<?php

namespace App\Providers;

use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\QueryBuilderCartRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios de repositorios del cart-service.
 *
 * Vincula las interfaces de repositorio a sus implementaciones concretas
 * con Query Builder. Esto permite desacoplar la lógica de negocio
 * (CartService) del mecanismo de persistencia.
 *
 * @see CartRepositoryInterface
 * @see QueryBuilderCartRepository
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CartRepositoryInterface::class => QueryBuilderCartRepository::class,
    ];

    /**
     * Registra bindings adicionales en el contenedor.
     * El binding principal está declarado en $bindings (Laravel 12).
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa servicios adicionales después del registro.
     */
    public function boot(): void
    {
        //
    }
}
