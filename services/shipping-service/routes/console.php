<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Comandos de consola de shipping-service
|--------------------------------------------------------------------------
|
| Aquí se registran comandos Artisan personalizados para tareas
| de mantenimiento, migración de datos legacy, sincronización
| de tablas, etc.
|
*/

/** Comando predeterminado de Laravel que muestra una frase inspiradora */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Mostrar una frase inspiradora');
