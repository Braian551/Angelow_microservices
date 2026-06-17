<?php

// Comentario de mantenimiento: Estas rutas conectan contratos HTTP con controladores del servicio.

use Illuminate\Support\Facades\Route;

// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.

Route::get('/', function () {
    return view('welcome');
});
