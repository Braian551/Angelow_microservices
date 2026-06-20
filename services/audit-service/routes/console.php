<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Rutas de consola (comandos Artisan)
|--------------------------------------------------------------------------
|
| Comando `inspire` por defecto de Laravel. Aquí se pueden registrar
| comandos personalizados para tareas de mantenimiento, limpieza de
| registros antiguos de auditoría o generación de reportes.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Muestra una cita inspiradora (comando por defecto de Laravel)');
