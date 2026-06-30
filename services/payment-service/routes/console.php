<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/**
 * Comandos de consola (Artisan) del servicio de pagos.
 * Incluye comandos de utilidad para desarrollo y testing.
 */

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
