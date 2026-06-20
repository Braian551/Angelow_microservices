<?php

namespace App\Jobs;

use App\Services\StockReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job que reconcilia las reservas de stock vencidas y los contadores en Redis.
 *
 * Se ejecuta periódicamente para liberar reservas expiradas y sincronizar
 * los contadores de stock reservado.
 */
class ReconcileStockReservationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('orders');
    }

    /**
     * Ejecuta la reconciliación de reservas vencidas y contadores.
     *
     * @param StockReservationService $reservationService Servicio de reservas de stock.
     */
    public function handle(StockReservationService $reservationService): void
    {
        $batchSize = max(1, (int) config('services.stock_reservations.reconciliation_batch_size', 200));

        // Reutiliza StockReservationService para cerrar reservas vencidas como cancelaciones operativas.
        $cancelledResult = $reservationService->reconcileExpiredReservations($batchSize);
        $counterResult = $reservationService->reconcileReservationCounters($batchSize);

        Log::info('Reconciliación de reservas de stock ejecutada.', [
            'cancelled_by_expiration' => $cancelledResult,
            'counters' => $counterResult,
        ]);
    }
}
