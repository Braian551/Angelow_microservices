<?php

/*
|--------------------------------------------------------------------------
| Proveedores de servicios registrados del cart-service
|--------------------------------------------------------------------------
|
| Lista de proveedores de servicios que Laravel cargará automáticamente.
| AppServiceProvider: configuración general de la aplicación.
| RepositoryServiceProvider: bindings de interfaces de repositorio.
|
| @see AppServiceProvider
| @see RepositoryServiceProvider
|
*/

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
