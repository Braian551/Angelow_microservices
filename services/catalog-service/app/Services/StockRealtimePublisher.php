<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class StockRealtimePublisher
{
    public function publish(string $event, array $payload): void
    {
        $baseChannel = trim((string) config('services.stock_realtime.ws_channel', 'ws:orders:stock'));
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

        try {
            Redis::publish($baseChannel, $message);
        } catch (Throwable $exception) {
            Log::warning('No se pudo publicar evento realtime de stock.', [
                'event' => $event,
                'channel' => $baseChannel,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
