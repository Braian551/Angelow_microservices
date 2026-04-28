<?php

namespace App\Console\Commands;

use App\Services\StockReservationService;
use Illuminate\Console\Command;

class ReconcileStockReservationsCommand extends Command
{
    protected $signature = 'reservations:reconcile {--batch=200 : Número máximo de órdenes a reconciliar por ejecución}';
    protected $description = 'Reconciliar reservas expiradas y contadores Redis de stock reservado.';

    public function handle(StockReservationService $reservationService): int
    {
        $batchSize = max(1, (int) $this->option('batch'));

        $expiredResult = $reservationService->reconcileExpiredReservations($batchSize);
        $counterResult = $reservationService->reconcileReservationCounters($batchSize);

        $this->info('Reconciliación de reservas completada.');
        $this->line('Expiradas: ' . json_encode($expiredResult, JSON_UNESCAPED_UNICODE));
        $this->line('Contadores: ' . json_encode($counterResult, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
