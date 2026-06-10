<?php

namespace App\Console\Commands;

use App\Services\StockReservationService;
use Illuminate\Console\Command;

class ReconcileStockReservationsCommand extends Command
{
    protected $signature = 'reservations:reconcile {--batch=200 : Número máximo de órdenes a reconciliar por ejecución}';
    protected $description = 'Reconciliar reservas vencidas como canceladas y contadores Redis de stock reservado.';

    public function handle(StockReservationService $reservationService): int
    {
        $batchSize = max(1, (int) $this->option('batch'));

        // Reutiliza StockReservationService para que scheduler y consola compartan la misma semántica de cancelación.
        $cancelledResult = $reservationService->reconcileExpiredReservations($batchSize);
        $counterResult = $reservationService->reconcileReservationCounters($batchSize);

        $this->info('Reconciliación de reservas completada.');
        $this->line('Canceladas por vencimiento: ' . json_encode($cancelledResult, JSON_UNESCAPED_UNICODE));
        $this->line('Contadores: ' . json_encode($counterResult, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
