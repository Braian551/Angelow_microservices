<?php

/*
|--------------------------------------------------------------------------
| Registro de proveedores de servicios (Service Providers)
|--------------------------------------------------------------------------
|
| Lista de proveedores que Laravel cargará durante el arranque.
| El orden afecta la disponibilidad de bindings: se cargan en
| la secuencia definida aquí.
|
| Proveedores registrados:
|   - AppServiceProvider: servicios generales de la aplicación.
|   - RepositoryServiceProvider: bindings de repositorios.
|
*/

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
