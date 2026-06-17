<?php

namespace App\Services;

// Comentario de mantenimiento: Este servicio concentra reglas de negocio para que los controladores no dupliquen lógica.

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Este servicio concentra reglas de negocio para que los controladores no dupliquen lógica.
 */
class StockRealtimePublisher
{
    /**
     * Publica un mensaje JSON en Redis si el canal de stock está configurado.
     */
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
