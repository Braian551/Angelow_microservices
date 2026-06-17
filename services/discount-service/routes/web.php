<?php

/**
 * Rutas web del servicio de descuentos.
 * Mayormente usadas para página de bienvenida y pruebas internas.
 */

use Illuminate\Support\Facades\Route;

// Ruta raíz: muestra la página de bienvenida del servicio de descuentos.
Route::get('/', function () {
    return view('welcome');
});
