<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web del servicio de autenticación
|--------------------------------------------------------------------------
|
| Ruta de bienvenida por defecto de Laravel. El auth-service es
| exclusivamente API, por lo que la ruta web solo existe para
| la página de inicio básica del framework.
|
*/

Route::get('/', function () {
    return view('welcome');
});
