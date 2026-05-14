<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Muestra una frase inspiradora');

Artisan::command('inventory:dispatch-alert-reminders {--dry-run : Solo reporta las alertas pendientes}', function () {
    $inventoryAlertService = app('App\\Services\\InventoryAlertService');
    $reconcileResult = $inventoryAlertService->reconcileCurrentInventory(!(bool) $this->option('dry-run'));
    $result = $inventoryAlertService->dispatchReminderEmails((bool) $this->option('dry-run'));

    $this->info('Variantes revisadas en conciliación: ' . $reconcileResult['scanned']);
    $this->info('Variantes agotadas detectadas: ' . $reconcileResult['out_of_stock']);
    $this->info('Alertas agotadas activas: ' . $result['total_out_of_stock']);
    $this->info('Alertas candidatas a recordatorio: ' . $result['due']);
    $this->info(($this->option('dry-run') ? 'Recordatorios simulados' : 'Recordatorios enviados') . ': ' . $result['sent']);

    foreach ($result['items'] as $item) {
        $this->line('- ' . $item['product_name'] . ' · ' . $item['label']);
    }
})->purpose('Envía recordatorios de inventario agotado');

Schedule::command('inventory:dispatch-alert-reminders')
    ->dailyAt('08:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping()
    ->name('catalog-inventory-alert-reminders');
