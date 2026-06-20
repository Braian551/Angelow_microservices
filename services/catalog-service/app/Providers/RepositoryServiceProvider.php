<?php

namespace App\Providers;

// Comentario de mantenimiento: Este proveedor registra dependencias y ajustes de arranque propios del servicio.

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\WishlistRepositoryInterface;
use App\Repositories\Contracts\SiteRepositoryInterface;
use App\Repositories\QueryBuilderProductRepository;
use App\Repositories\QueryBuilderCategoryRepository;
use App\Repositories\QueryBuilderWishlistRepository;
use App\Repositories\QueryBuilderSiteRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repository interfaces to their concrete Query Builder implementations.
 * This enables dependency injection and makes testing easier.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings.
     */
    public array $bindings = [
        ProductRepositoryInterface::class  => QueryBuilderProductRepository::class,
        CategoryRepositoryInterface::class => QueryBuilderCategoryRepository::class,
        WishlistRepositoryInterface::class => QueryBuilderWishlistRepository::class,
        SiteRepositoryInterface::class     => QueryBuilderSiteRepository::class,
    ];

    /**
     * Registra bindings y servicios necesarios durante el arranque de Laravel.
     */

    public function register(): void
    {
        //
    }

    /**
     * Ejecuta ajustes de arranque cuando el framework ya resolvió sus servicios.
     */

    public function boot(): void
    {
        //
    }
}
