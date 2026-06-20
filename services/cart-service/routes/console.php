<?php

/*
|--------------------------------------------------------------------------
| Comandos de consola del cart-service
|--------------------------------------------------------------------------
|
| Define comandos Artisan personalizados. Por defecto solo incluye
| el comando 'inspire' de Laravel. Los comandos de mantenimiento
| del carrito pueden agregarse aquí en el futuro.
|
*/

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Muestra una cita inspiradora');
