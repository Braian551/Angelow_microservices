<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\QueryBuilderUserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repository interfaces to their concrete implementations.
 * This enables dependency injection and makes testing easier.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings.
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
