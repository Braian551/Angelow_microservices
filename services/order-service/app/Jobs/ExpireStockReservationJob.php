<?php

namespace App\Jobs;

use App\Services\StockReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class ExpireStockReservationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;

    public function __construct(
        public readonly int $orderId,
    ) {
        $this->onQueue('orders');
    }

    public function handle(StockReservationService $reservationService): void
    {
        $result = $reservationService->expireReservation($this->orderId);
        if (!($result['ok'] ?? false)) {
            throw new RuntimeException((string) ($result['message'] ?? 'Falló expiración de reserva de stock.'));
        }

        if ((int) ($result['released'] ?? 0) <= 0) {
            return;
        }

        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status']);
        if ($statusColumn === null) {
            return;
        }

        DB::transaction(function () use ($statusColumn): void {
            $order = DB::table('orders')
                ->where('id', $this->orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return;
            }

            $currentStatus = Str::lower(trim((string) ($order->{$statusColumn} ?? '')));
            if (in_array($currentStatus, ['cancelled', 'canceled', 'completed', 'delivered', 'expired'], true)) {
                return;
            }

            DB::table('orders')
                ->where('id', $this->orderId)
                ->update([
                    $statusColumn => 'expired',
                    'updated_at' => now(),
                ]);

            if (Schema::hasTable('order_status_history')) {
                DB::table('order_status_history')->insert([
                    'order_id' => $this->orderId,
                    'changed_by' => 'system',
                    'changed_by_name' => 'Sistema',
                    'change_type' => 'status_change',
                    'field_changed' => 'status',
                    'old_value' => $currentStatus === '' ? null : $currentStatus,
                    'new_value' => 'expired',
                    'description' => 'La reserva de inventario expiró automáticamente por TTL.',
                    'created_at' => now(),
                ]);
            }
        });
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
