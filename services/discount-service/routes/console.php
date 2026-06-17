<?php

/**
 * Rutas de consola para el servicio de descuentos.
 * Comandos Artisan personalizados para tareas programadas y mantenimiento.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Comando de ejemplo que muestra una cita inspiradora en la consola.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Muestra una cita inspiradora');
