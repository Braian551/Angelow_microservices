<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class StockReservationRealtimePublisher
{
    public function publish(string $event, array $payload): void
    {
        $baseChannel = trim((string) config('services.stock_reservations.ws_channel', 'ws:orders:stock'));
        if ($baseChannel === '') {
            return;
        }

        $message = json_encode([
            'event' => $event,
            'payload' => $payload,
            'published_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE);

        if (!is_string($message) || $message === '') {
            return;
        }

        $channels = [$baseChannel];

        $orderId = (int) ($payload['order_id'] ?? 0);
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
