<?php

// Comentario de mantenimiento: Estas rutas conectan contratos HTTP con controladores del servicio.

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
