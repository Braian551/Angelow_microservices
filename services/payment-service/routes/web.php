<?php

use Illuminate\Support\Facades\Route;

/**
 * Rutas web del servicio de pagos.
 * Actualmente solo expone la vista de bienvenida por defecto de Laravel.
 * En arquitectura de microservicios, las APIs se definen en routes/api.php.
 */

Route::get('/', function () {
    return view('welcome');
});
