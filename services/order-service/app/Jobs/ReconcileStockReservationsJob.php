<?php

namespace App\Jobs;

use App\Services\StockReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReconcileStockReservationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('orders');
    }

    public function handle(StockReservationService $reservationService): void
    {
        $batchSize = max(1, (int) config('services.stock_reservations.reconciliation_batch_size', 200));
        $expiredResult = $reservationService->reconcileExpiredReservations($batchSize);
        $counterResult = $reservationService->reconcileReservationCounters($batchSize);

        Log::info('Reconciliación de reservas de stock ejecutada.', [
            'expired' => $expiredResult,
            'counters' => $counterResult,
        ]);
    }
}
