<?php

namespace App\Jobs;

use App\Services\StockReservationRealtimePublisher;
use App\Services\StockReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

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

    public function handle(StockReservationService $reservationService, StockReservationRealtimePublisher $realtimePublisher): void
    {
        $result = $reservationService->expireReservation($this->orderId);
        if (!($result['ok'] ?? false)) {
            throw new RuntimeException((string) ($result['message'] ?? 'Falló la cancelación automática de la reserva de stock.'));
        }

        if ((int) ($result['released'] ?? 0) <= 0) {
            return;
        }

        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status']);
        if ($statusColumn === null) {
            return;
        }

        $notificationPayload = DB::transaction(function () use ($statusColumn): ?array {
            $order = DB::table('orders')
                ->where('id', $this->orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return null;
            }

            $currentStatus = Str::lower(trim((string) ($order->{$statusColumn} ?? '')));
            if (in_array($currentStatus, ['cancelled', 'canceled', 'completed', 'delivered'], true)) {
                return null;
            }

            // La reserva vencida se materializa como cancelación de negocio para mantener una sola ruta final de inventario.
            DB::table('orders')
                ->where('id', $this->orderId)
                ->update([
                    $statusColumn => 'cancelled',
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
                    'new_value' => 'cancelled',
                    'description' => 'Orden cancelada automáticamente porque venció el tiempo configurado para completar el pago y sostener la reserva de inventario.',
                    'created_at' => now(),
                ]);
            }

            $orderLabel = trim((string) ($order->order_number ?? ''));
            if ($orderLabel === '') {
                $orderLabel = '#' . $this->orderId;
            }

            return [
                'order_id' => $this->orderId,
                'order_number' => $orderLabel,
                'user_id' => trim((string) ($order->user_id ?? '')),
                'old_status' => $currentStatus === '' ? null : $currentStatus,
                'new_status' => 'cancelled',
                'reason' => 'reservation_ttl_expired',
                'user_instruction' => 'Tu pedido fue cancelado porque el pago no se completó a tiempo. Puedes crear un nuevo pedido si los productos siguen disponibles.',
                'admin_instruction' => 'No reproceses esta orden como vencida; revisa disponibilidad actual y solicita al cliente crear un nuevo pedido si desea continuar.',
            ];
        });

        if ($notificationPayload === null) {
            return;
        }

        $realtimePublisher->publish('order.stock_reservation.auto_cancelled', $notificationPayload);
        $this->sendCustomerNotification($notificationPayload);
    }

    private function sendCustomerNotification(array $payload): void
    {
        $userId = trim((string) ($payload['user_id'] ?? ''));
        if ($userId === '') {
            return;
        }

        $endpoint = $this->resolveNotificationEndpoint();
        if ($endpoint === null) {
            return;
        }

        // Reutiliza el contrato de notification-service usado por OrderController para que la bandeja del cliente reciba el aviso accionable.
        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->post($endpoint, [
                    'user_id' => $userId,
                    'type_id' => (int) config('services.notifications.order_type_id', 1),
                    'title' => 'Tu pedido fue cancelado',
                    'message' => (string) ($payload['user_instruction'] ?? 'Tu pedido fue cancelado automáticamente.'),
                    'related_entity_type' => 'order',
                    'related_entity_id' => (int) ($payload['order_id'] ?? 0) ?: null,
                ]);

            if (!$response->successful()) {
                Log::warning('No se pudo registrar notificación de cancelación automática por reserva.', [
                    'order_id' => $payload['order_id'] ?? null,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Fallo enviando notificación de cancelación automática por reserva.', [
                'order_id' => $payload['order_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveNotificationEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.notifications.base_url', 'http://notification-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/notifications';
        }

        return $baseUrl . '/api/notifications';
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
