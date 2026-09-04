<?php

namespace App\Console\Commands;

use App\Http\Controllers\OrderController;
use Illuminate\Console\Command;

class CompleteDeliveredOrdersCommand extends Command
{
    protected $signature = 'orders:complete-expired-refund-windows {--batch=200 : Número máximo de pedidos a revisar por ejecución}';
    protected $description = 'Completa pedidos entregados al vencer su plazo de reembolso o cuando no aplica.';

    public function handle(OrderController $orderController): int
    {
        $batchSize = max(1, (int) $this->option('batch'));
        $result = $orderController->completeDeliveredOrdersWithExpiredRefundWindow($batchSize);

        $this->line('Cierre automático de pedidos: ' . json_encode($result, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
