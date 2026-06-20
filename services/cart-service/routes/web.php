<?php

/*
|--------------------------------------------------------------------------
| Rutas web del cart-service
|--------------------------------------------------------------------------
|
| Ruta de bienvenida que renderiza la vista welcome.blade.php.
| El cart-service es principalmente API; esta ruta solo existe
| para verificar que el servicio responde en navegador.
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
