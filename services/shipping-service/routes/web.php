<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web de shipping-service
|--------------------------------------------------------------------------
|
| Este microservicio es principalmente API, pero se mantiene la
| ruta web básica como punto de entrada para verificación visual
| y para el health check del contenedor.
|
*/

/** Ruta raíz que carga la vista de bienvenida predeterminada de Laravel */
Route::get('/', function () {
    return view('welcome');
});
