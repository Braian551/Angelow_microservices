<?php

/*
 * Rutas web para el servicio de órdenes.
 * Actualmente solo contiene la ruta de bienvenida.
 */

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
