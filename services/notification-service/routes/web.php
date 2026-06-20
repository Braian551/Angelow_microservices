<?php

/**
 * Rutas web del servicio de notificaciones.
 * Mayormente usadas para página de bienvenida y pruebas internas.
 */

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
