<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Publicador de eventos en tiempo real para reservas de stock.
 *
 * Envía notificaciones a través de Redis Pub/Sub para que
 * otros servicios (ej. WebSocket) puedan reaccionar a cambios
 * en las reservas de inventario.
 */
class StockReservationRealtimePublisher
{
    /**
     * Publica un evento en los canales de Redis.
     *
     * @param string $event Nombre del evento (ej. 'order.stock_reservation.auto_cancelled').
     * @param array $payload Datos asociados al evento.
     */
    public function publish(string $event, array $payload): void
    {
        $baseChannel = trim((string) config('services.stock_reservations.ws_channel', 'ws:orders:stock'));
        // Si no hay canal configurado, salir sin publicar
        if ($baseChannel === '') {
            return;
        }

        $message = json_encode([
            'event' => $event,
            'payload' => $payload,
            'published_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE);

        // Si el mensaje no se pudo codificar, salir
        if (!is_string($message) || $message === '') {
            return;
        }

        $channels = [$baseChannel];

        $orderId = (int) ($payload['order_id'] ?? 0);
        // Si hay un ID de orden, añadir un canal específico para esa orden
        if ($orderId > 0) {
            $channels[] = "{$baseChannel}:order:{$orderId}";
        }

        try {
            foreach (array_unique($channels) as $channel) {
                Redis::publish($channel, $message);
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo publicar evento realtime de reserva de stock.', [
                'event' => $event,
                'channels' => $channels,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
