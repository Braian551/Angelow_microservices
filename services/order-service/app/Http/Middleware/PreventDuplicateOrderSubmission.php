<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PreventDuplicateOrderSubmission
{
    private const LOCK_TTL_SECONDS = 20;
    private const LOCK_ATTRIBUTE = '_order_submission_lock';

    public function handle(Request $request, Closure $next): Response
    {
        $orderNumber = trim((string) $request->input('order_number', ''));
        if ($orderNumber === '') {
            return $next($request);
        }

        $existingOrderId = $this->findOrderIdByOrderNumber($orderNumber);
        if ($existingOrderId !== null) {
            return $this->duplicateResponse($existingOrderId);
        }

        try {
            $lock = Cache::store('redis')->lock($this->lockKey($orderNumber), self::LOCK_TTL_SECONDS);
            if (!$lock->get()) {
                $existingOrderId = $this->findOrderIdByOrderNumber($orderNumber);
                if ($existingOrderId !== null) {
                    return $this->duplicateResponse($existingOrderId);
                }

                return response()->json([
                    'message' => 'Ya estamos procesando esta orden. Espera unos segundos e intenta nuevamente.',
                ], 429);
            }

            $request->attributes->set(self::LOCK_ATTRIBUTE, $lock);
        } catch (Throwable $exception) {
            Log::warning('No se pudo aplicar lock anti-duplicados en creación de orden.', [
                'order_number' => $orderNumber,
                'error' => $exception->getMessage(),
            ]);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $lock = $request->attributes->get(self::LOCK_ATTRIBUTE);
        if (!$lock instanceof Lock) {
            return;
        }

        try {
            $lock->release();
        } catch (Throwable) {
            // No bloquea la respuesta al usuario.
        }
    }

    private function duplicateResponse(int $orderId): JsonResponse
    {
        return response()->json([
            'message' => 'La orden ya fue registrada previamente.',
            'id' => $orderId,
            'duplicate' => true,
        ]);
    }

    private function findOrderIdByOrderNumber(string $orderNumber): ?int
    {
        $orderId = DB::table('orders')
            ->where('order_number', $orderNumber)
            ->value('id');

        return $orderId !== null ? (int) $orderId : null;
    }

    private function lockKey(string $orderNumber): string
    {
        return 'lock:order:create:' . $orderNumber;
    }
}
