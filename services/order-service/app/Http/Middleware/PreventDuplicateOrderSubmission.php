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

/**
 * Middleware que previene el envío duplicado de órdenes.
 *
 * Utiliza un lock distribuido en Redis para asegurar que una misma
 * orden (identificada por su order_number) no sea procesada más de una vez
 * simultáneamente.
 */
class PreventDuplicateOrderSubmission
{
    private const LOCK_TTL_SECONDS = 20;
    private const LOCK_ATTRIBUTE = '_order_submission_lock';

    /**
     * Maneja la petición entrante.
     *
     * @param Request $request Petición HTTP entrante.
     * @param Closure $next Siguiente middleware o controlador.
     * @return Response Respuesta HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $orderNumber = trim((string) $request->input('order_number', ''));
        // Si no hay número de orden, continuar sin validación
        if ($orderNumber === '') {
            return $next($request);
        }

        $existingOrderId = $this->findOrderIdByOrderNumber($orderNumber);
        // Si la orden ya existe, retornar respuesta de duplicado
        if ($existingOrderId !== null) {
            return $this->duplicateResponse($existingOrderId);
        }

        try {
            $lock = Cache::store('redis')->lock($this->lockKey($orderNumber), self::LOCK_TTL_SECONDS);
            if (!$lock->get()) {
                // Si no se pudo adquirir el lock, verificar nuevamente si la orden ya fue creada
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

    /**
     * Libera el lock después de que la respuesta se haya enviado.
     *
     * @param Request $request Petición HTTP.
     * @param Response $response Respuesta HTTP.
     */
    public function terminate(Request $request, Response $response): void
    {
        $lock = $request->attributes->get(self::LOCK_ATTRIBUTE);
        // Si no hay lock asociado, no hacer nada
        if (!$lock instanceof Lock) {
            return;
        }

        try {
            $lock->release();
        } catch (Throwable) {
            // No bloquea la respuesta al usuario.
        }
    }

    /**
     * Retorna una respuesta JSON indicando que la orden es duplicada.
     *
     * @param int $orderId Identificador de la orden existente.
     * @return JsonResponse Respuesta de duplicado.
     */
    private function duplicateResponse(int $orderId): JsonResponse
    {
        return response()->json([
            'message' => 'La orden ya fue registrada previamente.',
            'id' => $orderId,
            'duplicate' => true,
        ]);
    }

    /**
     * Busca el ID de una orden por su número de orden.
     *
     * @param string $orderNumber Número de orden a buscar.
     * @return int|null ID de la orden o nulo si no existe.
     */
    private function findOrderIdByOrderNumber(string $orderNumber): ?int
    {
        $orderId = DB::table('orders')
            ->where('order_number', $orderNumber)
            ->value('id');

        return $orderId !== null ? (int) $orderId : null;
    }

    /**
     * Genera la clave del lock en Redis para un número de orden.
     *
     * @param string $orderNumber Número de orden.
     * @return string Clave del lock.
     */
    private function lockKey(string $orderNumber): string
    {
        return 'lock:order:create:' . $orderNumber;
    }
}
