<?php

/*
|--------------------------------------------------------------------------
| Registro de proveedores de servicios (Service Providers)
|--------------------------------------------------------------------------
|
| Lista de proveedores que Laravel cargará durante el arranque.
| El orden afecta la disponibilidad de bindings.
|
| Proveedores registrados:
|   - AppServiceProvider: servicios generales de la aplicación
|   - RepositoryServiceProvider: bindings de interfaces de repositorio
|
*/

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
