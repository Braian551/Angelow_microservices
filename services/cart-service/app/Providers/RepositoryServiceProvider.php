<?php

namespace App\Providers;

use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\QueryBuilderCartRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repository interfaces to their concrete Query Builder implementations.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CartRepositoryInterface::class => QueryBuilderCartRepository::class,
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
