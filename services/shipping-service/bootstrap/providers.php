<?php

/*
|--------------------------------------------------------------------------
| Proveedores de servicios registrados para shipping-service
|--------------------------------------------------------------------------
|
| Lista de service providers que Laravel debe cargar al iniciar.
| AppServiceProvider: registro de servicios generales de la aplicación.
| RepositoryServiceProvider: preparado para futuros bindings de repositorios.
|
*/

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
];
