<?php

namespace App\Http\Controllers;

use App\Jobs\ExpireStockReservationJob;
use App\Services\OrderInvoiceService;
use App\Services\StockReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class OrderController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';
    private const DEFAULT_NOTIFICATION_TYPE_ID = 1;
    private const REVIEWABLE_ORDER_STATUSES = ['pending', 'pending_payment', 'created'];
    private const REVIEW_STATUS = 'in_review';
    private const CONFIRM_RESERVATION_STATUS_VALUES = ['paid', 'confirmed', 'processing', 'completed', 'delivered'];
    private const RELEASE_RESERVATION_STATUS_VALUES = ['cancelled', 'canceled', 'rejected', 'failed', 'expired'];
    private const CONFIRM_RESERVATION_PAYMENT_VALUES = ['paid', 'approved', 'verified'];
    private const RELEASE_RESERVATION_PAYMENT_VALUES = ['rejected', 'failed', 'cancelled', 'canceled'];

    public function __construct(
        private readonly OrderInvoiceService $orderInvoiceService,
        private readonly StockReservationService $stockReservationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $status = $request->filled('status')
            ? $request->string('status')->toString()
            : null;

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if (empty($candidateUserIds)) {
            $orders = $this->fetchOrdersByConnection(null, null, $status);

            // Fallback temporal durante migración: usa legacy cuando la BD distribuida aún está vacía.
            if ($orders->isEmpty()) {
                $orders = $this->fetchOrdersByConnection(self::LEGACY_CONNECTION, null, $status);
            }

            return response()->json([
                'data' => $orders,
            ]);
        }

        $orders = $this->fetchOrdersForCandidates(null, $candidateUserIds, $status);

        // Fallback temporal durante migración: usa legacy si no hay datos distribuidos.
        if ($orders->isEmpty()) {
            $orders = $this->fetchOrdersForCandidates(self::LEGACY_CONNECTION, $candidateUserIds, $status);
        }

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $preferredConnection = $this->normalizeSourceConnection($request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $order = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);

        $items = $this->fetchOrderItemsByConnection($sourceConnection, $id);
        $items = $this->enrichOrderItemsWithCatalogImages($items);
        $history = $this->fetchOrderHistoryByConnection($sourceConnection, $id);

        return response()->json([
            'order' => $order,
            'items' => $items,
            'history' => $history,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:20'],
            'user_id' => ['nullable', 'string', 'max:20'],
            'subtotal' => ['required', 'numeric'],
            'shipping_cost' => ['nullable', 'numeric'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_code' => ['nullable', 'string', 'max:50'],
            'discount_source' => ['nullable', 'string', 'max:30'],
            'total' => ['required', 'numeric'],
            'status' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'payment_status' => ['nullable', 'string', 'max:20'],
            'shipping_address' => ['nullable', 'string'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_method_id' => ['nullable', 'integer'],
            'billing_name' => ['nullable', 'string', 'max:150'],
            'billing_document' => ['nullable', 'string', 'max:40'],
            'billing_email' => ['nullable', 'string', 'email', 'max:255'],
            'billing_phone' => ['nullable', 'string', 'max:30'],
            'billing_address' => ['nullable', 'string'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer'],
            'items.*.color_variant_id' => ['nullable', 'integer'],
            'items.*.size_variant_id' => ['required_with:items', 'integer', 'min:1'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['required_with:items', 'numeric'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.total' => ['required_with:items', 'numeric'],
        ]);

        $existingOrderId = DB::table('orders')
            ->where('order_number', $data['order_number'])
            ->value('id');

        if ($existingOrderId !== null) {
            return response()->json([
                'message' => 'La orden ya fue registrada previamente.',
                'id' => (int) $existingOrderId,
                'duplicate' => true,
            ]);
        }

        $reservationResult = null;
        $id = null;
        DB::beginTransaction();

        try {
            $ordersTable = 'orders';
            $orderPayload = [
                'order_number' => $data['order_number'],
                'user_id' => $data['user_id'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'subtotal' => $data['subtotal'],
                'total' => $data['total'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn($ordersTable, 'discount_amount')) {
                $orderPayload['discount_amount'] = (float) ($data['discount_amount'] ?? 0);
            }

            $optionalOrderFields = [
                'shipping_cost' => $data['shipping_cost'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_status' => $data['payment_status'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_method_id' => $data['shipping_method_id'] ?? null,
                'discount_code' => $data['discount_code'] ?? null,
                'discount_source' => $data['discount_source'] ?? null,
                'billing_name' => $data['billing_name'] ?? null,
                'billing_document' => $data['billing_document'] ?? null,
                'billing_email' => $data['billing_email'] ?? null,
                'billing_phone' => $data['billing_phone'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'billing_city' => $data['billing_city'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            foreach ($optionalOrderFields as $column => $value) {
                if (!Schema::hasColumn($ordersTable, $column)) {
                    continue;
                }

                if ($value !== null && $value !== '') {
                    $orderPayload[$column] = $value;
                }
            }

            $id = (int) DB::table('orders')->insertGetId($orderPayload);

            if (Schema::hasTable('order_items')) {
                $itemsTable = 'order_items';

                foreach ($data['items'] as $item) {
                    $itemPayload = [
                        'order_id' => $id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'total' => $item['total'],
                    ];

                    $optionalItemFields = [
                        'color_variant_id' => $item['color_variant_id'] ?? null,
                        'size_variant_id' => $item['size_variant_id'] ?? null,
                        'variant_name' => $item['variant_name'] ?? null,
                    ];

                    foreach ($optionalItemFields as $column => $value) {
                        if (!Schema::hasColumn($itemsTable, $column)) {
                            continue;
                        }

                        $itemPayload[$column] = $value;
                    }

                    if (Schema::hasColumn($itemsTable, 'created_at')) {
                        $itemPayload['created_at'] = now();
                    }

                    DB::table('order_items')->insert($itemPayload);
                }
            }

            $reservationTtl = (int) config('services.stock_reservations.ttl_seconds', 7200);
            $reservationResult = $this->stockReservationService->reserveForOrder(
                orderId: $id,
                orderNumber: (string) $data['order_number'],
                items: $data['items'],
                ttlSeconds: $reservationTtl,
            );

            if (!($reservationResult['ok'] ?? false)) {
                DB::rollBack();

                return response()->json([
                    'message' => $reservationResult['message'] ?? 'No fue posible reservar inventario para crear la orden.',
                    'code' => $reservationResult['code'] ?? 'reservation_failed',
                ], (int) ($reservationResult['http_status'] ?? 422));
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        if (!empty($reservationResult['expires_at'])) {
            try {
                ExpireStockReservationJob::dispatch($id)
                    ->delay(Carbon::parse((string) $reservationResult['expires_at']))
                    ->onQueue('orders');
            } catch (Throwable $exception) {
                Log::warning('No se pudo programar expiración de reserva de stock.', [
                    'order_id' => $id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $createdOrder = $this->query(null)->table('orders')->where('id', $id)->first();
            if ($createdOrder) {
                $this->notifyOrderCreationChannels(
                    $this->hydrateOrderCustomerIdentity($createdOrder, null),
                    null,
                );
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo notificar la creación de la orden.', [
                'order_id' => $id ?? null,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Orden creada',
            'id' => $id,
            'reservation' => [
                'expires_at' => $reservationResult['expires_at'] ?? null,
                'ttl_seconds' => $reservationResult['ttl_seconds'] ?? null,
            ],
        ], 201);
    }

    public function sendCheckoutConfirmation(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:20'],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'payment_proof_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($data['source'] ?? $request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        if ($this->supportsReservationWorkflow($sourceConnection)) {
            $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $sourceConnection);
            $oldStatus = $statusColumn
                ? ($order->{$statusColumn} ?? $order->status ?? $order->order_status ?? null)
                : ($order->status ?? $order->order_status ?? null);

            if ($statusColumn !== null && $this->shouldMoveOrderToReview($oldStatus)) {
                $this->query($sourceConnection)->table('orders')->where('id', $id)->update([
                    $statusColumn => self::REVIEW_STATUS,
                    'updated_at' => now(),
                ]);

                $this->insertOrderHistory($sourceConnection, [
                    'order_id' => $id,
                    'changed_by' => null,
                    'changed_by_name' => 'Cliente',
                    'change_type' => 'status_change',
                    'field_changed' => 'status',
                    'old_value' => $oldStatus,
                    'new_value' => self::REVIEW_STATUS,
                    'description' => 'Comprobante recibido. La orden quedo en revision de pago.',
                    'created_at' => now(),
                ]);

                $this->notifyOrderUpdateChannels(
                    $this->hydrateOrderCustomerIdentity($order, $sourceConnection),
                    $sourceConnection,
                    'status',
                    $oldStatus,
                    self::REVIEW_STATUS,
                );
            }

            $extensionTtl = (int) config('services.stock_reservations.extend_on_confirmation_seconds', 1800);
            if ($extensionTtl > 0) {
                try {
                    $this->stockReservationService->extendReservation($id, $extensionTtl);
                } catch (Throwable $exception) {
                    Log::warning('No se pudo extender la reserva tras envio de comprobante.', [
                        'order_id' => $id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $result = $this->orderInvoiceService->sendCheckoutConfirmationEmail($id, [
            'payment_reference' => $data['payment_reference'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'payment_proof_name' => $data['payment_proof_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
        ], $preferredConnection);

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'No se pudo enviar el correo de confirmación.',
            ], (int) ($result['code'] ?? 422));
        }

        return response()->json([
            'message' => 'Correo de confirmación enviado.',
            'data' => $result,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:20'],
            'changed_by' => ['nullable', 'string', 'max:20'],
            'changed_by_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $sourceConnection);
        if (!$statusColumn) {
            return response()->json(['message' => 'No existe columna de estado en la orden'], 422);
        }

        if ($this->supportsReservationWorkflow($sourceConnection)) {
            $releaseResult = $this->stockReservationService->releaseReservation(
                orderId: $id,
                targetStatus: 'cancelled',
                reason: 'order_deactivated',
            );

            if (!($releaseResult['ok'] ?? false)) {
                return response()->json([
                    'message' => $releaseResult['message'] ?? 'No fue posible liberar la reserva de inventario.',
                    'code' => $releaseResult['code'] ?? 'reservation_release_failed',
                ], (int) ($releaseResult['http_status'] ?? 422));
            }
        }

        if ($this->supportsReservationWorkflow($sourceConnection)) {
            $releaseResult = $this->stockReservationService->releaseReservation(
                orderId: $id,
                targetStatus: 'cancelled',
                reason: 'customer_cancelled_order',
            );

            if (!($releaseResult['ok'] ?? false)) {
                return response()->json([
                    'message' => $releaseResult['message'] ?? 'No fue posible liberar la reserva de inventario.',
                    'code' => $releaseResult['code'] ?? 'reservation_release_failed',
                ], (int) ($releaseResult['http_status'] ?? 422));
            }
        }

        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $sourceConnection);
        $oldStatus = $order->{$statusColumn} ?? $order->status ?? $order->order_status ?? null;
        $oldPaymentStatus = $paymentStatusColumn
            ? ($order->{$paymentStatusColumn} ?? $order->payment_status ?? null)
            : ($order->payment_status ?? null);
        $shouldMoveToPendingRefund = $paymentStatusColumn !== null
            && $this->shouldMoveToPendingRefund($data['status'], $oldPaymentStatus, $oldStatus);
        $newPaymentStatus = $shouldMoveToPendingRefund ? 'pending_refund' : null;
        $targetStatus = (string) $data['status'];

        if ($this->supportsReservationWorkflow($sourceConnection)) {
            if ($this->shouldConfirmReservationForOrderStatus($targetStatus)) {
                $confirmResult = $this->stockReservationService->confirmReservation($id);
                if (!($confirmResult['ok'] ?? false)) {
                    $errorCode = (string) ($confirmResult['code'] ?? 'reservation_confirm_failed');
                    $errorMessage = (string) ($confirmResult['message'] ?? 'No fue posible confirmar la reserva de inventario.');
                    $httpStatus = (int) ($confirmResult['http_status'] ?? 422);

                    return response()->json([
                        'message' => $errorMessage,
                        'code' => $errorCode,
                    ], $httpStatus);
                }
            } elseif ($this->shouldReleaseReservationForOrderStatus($targetStatus)) {
                $releaseResult = $this->stockReservationService->releaseReservation(
                    orderId: $id,
                    targetStatus: 'cancelled',
                    reason: 'status_changed_to_' . Str::lower(trim((string) $targetStatus)),
                );

                if (!($releaseResult['ok'] ?? false)) {
                    $errorCode = (string) ($releaseResult['code'] ?? 'reservation_release_failed');
                    $errorMessage = (string) ($releaseResult['message'] ?? 'No fue posible liberar la reserva de inventario.');
                    $httpStatus = (int) ($releaseResult['http_status'] ?? 422);

                    return response()->json([
                        'message' => $errorMessage,
                        'code' => $errorCode,
                    ], $httpStatus);
                }
            }
        }

        $updatePayload = [
            $statusColumn => $data['status'],
            'updated_at' => now(),
        ];

        if ($newPaymentStatus !== null) {
            $updatePayload[$paymentStatusColumn] = $newPaymentStatus;
        }

        $this->query($sourceConnection)->table('orders')->where('id', $id)->update($updatePayload);

        $this->insertOrderHistory($sourceConnection, [
            'order_id' => $id,
            'changed_by' => $data['changed_by'] ?? null,
            'changed_by_name' => $data['changed_by_name'] ?? null,
            'change_type' => 'status_change',
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $data['status'],
            'description' => $data['description'] ?? 'Cambio de estado de la orden',
            'created_at' => now(),
        ]);

        if ($newPaymentStatus !== null && !$this->sameNormalizedValue($oldPaymentStatus, $newPaymentStatus)) {
            $this->insertOrderHistory($sourceConnection, [
                'order_id' => $id,
                'changed_by' => $data['changed_by'] ?? null,
                'changed_by_name' => $data['changed_by_name'] ?? null,
                'change_type' => 'payment_change',
                'field_changed' => 'payment_status',
                'old_value' => $oldPaymentStatus,
                'new_value' => $newPaymentStatus,
                'description' => 'Actualización automática del estado de pago por cancelación de la orden.',
                'created_at' => now(),
            ]);
        }

        $hydratedOrder = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);

        if (!$this->sameNormalizedValue($oldStatus, $data['status'])) {
            $this->notifyOrderUpdateChannels(
                $hydratedOrder,
                $sourceConnection,
                'status',
                $oldStatus,
                $data['status'],
            );
        }

        if ($newPaymentStatus !== null && !$this->sameNormalizedValue($oldPaymentStatus, $newPaymentStatus)) {
            $this->notifyOrderUpdateChannels(
                $hydratedOrder,
                $sourceConnection,
                'payment_status',
                $oldPaymentStatus,
                $newPaymentStatus,
            );
        }

        $invoiceResult = $this->orderInvoiceService->ensureInvoiceForCompletedOrder($id, $sourceConnection);

        return response()->json([
            'message' => 'Estado actualizado',
            'invoice' => $invoiceResult,
        ]);
    }

    public function updatePaymentStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', 'string', 'max:20'],
            'changed_by' => ['nullable', 'string', 'max:20'],
            'changed_by_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $sourceConnection);
        if (!$paymentStatusColumn) {
            return response()->json(['message' => 'La orden no tiene columna payment_status'], 422);
        }

        $oldPaymentStatus = $order->{$paymentStatusColumn} ?? $order->payment_status ?? null;
        $targetPaymentStatus = (string) $data['payment_status'];
        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $sourceConnection);
        $oldStatus = $statusColumn
            ? ($order->{$statusColumn} ?? $order->status ?? $order->order_status ?? null)
            : ($order->status ?? $order->order_status ?? null);

        if ($this->supportsReservationWorkflow($sourceConnection)) {
            if ($this->shouldConfirmReservationForPaymentStatus($targetPaymentStatus)) {
                $confirmResult = $this->stockReservationService->confirmReservation($id);

                if (!($confirmResult['ok'] ?? false)) {
                    $code = (string) ($confirmResult['code'] ?? 'reservation_confirm_failed');
                    $message = (string) ($confirmResult['message'] ?? 'No fue posible confirmar inventario para esta orden.');
                    $httpStatus = (int) ($confirmResult['http_status'] ?? 422);

                    if ($code === 'insufficient_stock') {
                        $this->cancelOrderByInventoryConflict(
                            orderId: $id,
                            order: $order,
                            sourceConnection: $sourceConnection,
                            changedBy: $data['changed_by'] ?? null,
                            changedByName: $data['changed_by_name'] ?? null,
                        );

                        return response()->json([
                            'message' => 'No hay stock disponible para confirmar este pago. La orden fue cancelada automaticamente.',
                            'code' => 'insufficient_stock',
                            'order_cancelled' => true,
                        ], 409);
                    }

                    return response()->json([
                        'message' => $message,
                        'code' => $code,
                    ], $httpStatus);
                }
            } elseif ($this->shouldReleaseReservationForPaymentStatus($targetPaymentStatus)) {
                $releaseResult = $this->stockReservationService->releaseReservation(
                    orderId: $id,
                    targetStatus: 'cancelled',
                    reason: 'payment_status_changed_to_' . Str::lower(trim($targetPaymentStatus)),
                );

                if (!($releaseResult['ok'] ?? false)) {
                    $errorCode = (string) ($releaseResult['code'] ?? 'reservation_release_failed');
                    $errorMessage = (string) ($releaseResult['message'] ?? 'No fue posible liberar la reserva de inventario.');
                    $httpStatus = (int) ($releaseResult['http_status'] ?? 422);

                    return response()->json([
                        'message' => $errorMessage,
                        'code' => $errorCode,
                    ], $httpStatus);
                }
            }
        }

        $shouldAutoCancelOrder = $statusColumn !== null
            && $this->shouldAutoCancelOrderForPaymentStatus($targetPaymentStatus)
            && !$this->sameNormalizedValue($oldStatus, 'cancelled')
            && !$this->sameNormalizedValue($oldStatus, 'canceled');

        $updatePayload = [
            $paymentStatusColumn => $targetPaymentStatus,
            'updated_at' => now(),
        ];

        if ($shouldAutoCancelOrder) {
            $updatePayload[$statusColumn] = 'cancelled';
        }

        $this->query($sourceConnection)->table('orders')->where('id', $id)->update($updatePayload);

        $this->insertOrderHistory($sourceConnection, [
            'order_id' => $id,
            'changed_by' => $data['changed_by'] ?? null,
            'changed_by_name' => $data['changed_by_name'] ?? null,
            'change_type' => 'payment_change',
            'field_changed' => 'payment_status',
            'old_value' => $oldPaymentStatus,
            'new_value' => $targetPaymentStatus,
            'description' => $data['description'] ?? 'Cambio de estado de pago de la orden',
            'created_at' => now(),
        ]);

        if ($shouldAutoCancelOrder) {
            $this->insertOrderHistory($sourceConnection, [
                'order_id' => $id,
                'changed_by' => $data['changed_by'] ?? null,
                'changed_by_name' => $data['changed_by_name'] ?? null,
                'change_type' => 'status_change',
                'field_changed' => 'status',
                'old_value' => $oldStatus,
                'new_value' => 'cancelled',
                'description' => 'Orden cancelada automaticamente tras rechazo del pago.',
                'created_at' => now(),
            ]);
        }

        if ($shouldAutoCancelOrder) {
            $order->status = 'cancelled';
        }
        $order->payment_status = $targetPaymentStatus;
        $hydratedOrder = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);

        if ($shouldAutoCancelOrder) {
            $this->notifyOrderCancellationChannels(
                $hydratedOrder,
                $sourceConnection,
                false,
                'Pago rechazado por validacion administrativa.',
            );
        }

        if (!$this->sameNormalizedValue($oldPaymentStatus, $targetPaymentStatus)) {
            $this->notifyOrderUpdateChannels(
                $hydratedOrder,
                $sourceConnection,
                'payment_status',
                $oldPaymentStatus,
                $targetPaymentStatus,
            );
        }

        $invoiceResult = $this->orderInvoiceService->ensureInvoiceForCompletedOrder($id, $sourceConnection);

        return response()->json([
            'message' => 'Estado de pago actualizado',
            'invoice' => $invoiceResult,
        ]);
    }

    public function deactivate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'changed_by' => ['nullable', 'string', 'max:20'],
            'changed_by_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $sourceConnection);
        if (!$statusColumn) {
            return response()->json(['message' => 'No existe columna de estado en la orden'], 422);
        }

        $oldStatus = $order->{$statusColumn} ?? $order->status ?? $order->order_status ?? null;
        if ($this->sameNormalizedValue($oldStatus, 'cancelled') || $this->sameNormalizedValue($oldStatus, 'canceled')) {
            return response()->json(['message' => 'La orden ya esta desactivada']);
        }

        if ($this->supportsReservationWorkflow($sourceConnection)) {
            $releaseResult = $this->stockReservationService->releaseReservation(
                orderId: $id,
                targetStatus: 'cancelled',
                reason: 'order_deactivated',
            );

            if (!($releaseResult['ok'] ?? false)) {
                return response()->json([
                    'message' => $releaseResult['message'] ?? 'No fue posible liberar la reserva de inventario.',
                    'code' => $releaseResult['code'] ?? 'reservation_release_failed',
                ], (int) ($releaseResult['http_status'] ?? 422));
            }
        }

        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $sourceConnection);
        $oldPaymentStatus = $paymentStatusColumn
            ? ($order->{$paymentStatusColumn} ?? $order->payment_status ?? null)
            : ($order->payment_status ?? null);
        $shouldMoveToPendingRefund = $paymentStatusColumn !== null
            && $this->shouldMoveToPendingRefund('cancelled', $oldPaymentStatus, $oldStatus);
        $newPaymentStatus = $shouldMoveToPendingRefund ? 'pending_refund' : null;

        $updatePayload = [
            $statusColumn => 'cancelled',
            'updated_at' => now(),
        ];

        if ($newPaymentStatus !== null) {
            $updatePayload[$paymentStatusColumn] = $newPaymentStatus;
        }

        $this->query($sourceConnection)->table('orders')->where('id', $id)->update($updatePayload);

        $this->insertOrderHistory($sourceConnection, [
            'order_id' => $id,
            'changed_by' => $data['changed_by'] ?? null,
            'changed_by_name' => $data['changed_by_name'] ?? null,
            'change_type' => 'status_change',
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => 'cancelled',
            'description' => $data['description'] ?? 'Orden desactivada desde administracion',
            'created_at' => now(),
        ]);

        if ($newPaymentStatus !== null && !$this->sameNormalizedValue($oldPaymentStatus, $newPaymentStatus)) {
            $this->insertOrderHistory($sourceConnection, [
                'order_id' => $id,
                'changed_by' => $data['changed_by'] ?? null,
                'changed_by_name' => $data['changed_by_name'] ?? null,
                'change_type' => 'payment_change',
                'field_changed' => 'payment_status',
                'old_value' => $oldPaymentStatus,
                'new_value' => $newPaymentStatus,
                'description' => 'Actualizacion automatica del estado de pago por cancelacion de la orden.',
                'created_at' => now(),
            ]);
        }

        $hydratedOrder = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);

        $this->notifyOrderUpdateChannels(
            $hydratedOrder,
            $sourceConnection,
            'status',
            $oldStatus,
            'cancelled',
        );

        if ($newPaymentStatus !== null && !$this->sameNormalizedValue($oldPaymentStatus, $newPaymentStatus)) {
            $this->notifyOrderUpdateChannels(
                $hydratedOrder,
                $sourceConnection,
                'payment_status',
                $oldPaymentStatus,
                $newPaymentStatus,
            );
        }

        return response()->json([
            'message' => 'Orden desactivada',
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:20'],
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'cancelled_by_name' => ['nullable', 'string', 'max:100'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($data['source'] ?? $request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        if (!$this->canCustomerManageOrder(
            $order,
            $sourceConnection,
            $data['user_id'] ?? null,
            $data['user_email'] ?? null,
        )) {
            return response()->json([
                'message' => 'No tienes permisos para cancelar esta orden.',
            ], 403);
        }

        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $sourceConnection);
        if (!$statusColumn) {
            return response()->json(['message' => 'No existe columna de estado en la orden'], 422);
        }

        $oldStatus = trim((string) ($order->{$statusColumn} ?? $order->status ?? $order->order_status ?? ''));
        if ($this->sameNormalizedValue($oldStatus, 'cancelled') || $this->sameNormalizedValue($oldStatus, 'canceled')) {
            return response()->json([
                'message' => 'La orden ya se encuentra cancelada.',
            ], 422);
        }

        if (!$this->isCustomerCancelableStatus($oldStatus)) {
            return response()->json([
                'message' => 'Solo se pueden cancelar pedidos pendientes o en proceso.',
            ], 422);
        }

        if ($this->supportsReservationWorkflow($sourceConnection)) {
            $releaseResult = $this->stockReservationService->releaseReservation(
                orderId: $id,
                targetStatus: 'cancelled',
                reason: 'customer_cancelled_order',
            );

            if (!($releaseResult['ok'] ?? false)) {
                return response()->json([
                    'message' => $releaseResult['message'] ?? 'No fue posible liberar la reserva de inventario.',
                    'code' => $releaseResult['code'] ?? 'reservation_release_failed',
                ], (int) ($releaseResult['http_status'] ?? 422));
            }
        }

        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $sourceConnection);
        $oldPaymentStatus = $paymentStatusColumn
            ? trim((string) ($order->{$paymentStatusColumn} ?? $order->payment_status ?? ''))
            : trim((string) ($order->payment_status ?? ''));

        $requiresRefund = $this->requiresRefundOnCancel($oldPaymentStatus, $oldStatus);
        $newPaymentStatus = $requiresRefund && $paymentStatusColumn ? 'pending_refund' : null;

        $updatePayload = [
            $statusColumn => 'cancelled',
            'updated_at' => now(),
        ];

        if ($newPaymentStatus !== null) {
            $updatePayload[$paymentStatusColumn] = $newPaymentStatus;
        }

        $this->query($sourceConnection)->table('orders')->where('id', $id)->update($updatePayload);

        $customerReason = $this->nullableString($data['reason'] ?? null);
        $historyDescription = $requiresRefund
            ? 'Orden cancelada por el cliente. Reembolso en proceso.'
            : 'Orden cancelada por el cliente.';
        if ($customerReason !== null) {
            $historyDescription .= ' Motivo: ' . $customerReason;
        }

        $this->insertOrderHistory($sourceConnection, [
            'order_id' => $id,
            'changed_by' => $this->nullableString($data['user_id'] ?? null),
            'changed_by_name' => $this->nullableString($data['cancelled_by_name'] ?? null),
            'change_type' => 'status_change',
            'field_changed' => 'status',
            'old_value' => $oldStatus,
            'new_value' => 'cancelled',
            'description' => $historyDescription,
            'created_at' => now(),
        ]);

        if ($newPaymentStatus !== null && !$this->sameNormalizedValue($oldPaymentStatus, $newPaymentStatus)) {
            $this->insertOrderHistory($sourceConnection, [
                'order_id' => $id,
                'changed_by' => $this->nullableString($data['user_id'] ?? null),
                'changed_by_name' => $this->nullableString($data['cancelled_by_name'] ?? null),
                'change_type' => 'payment_change',
                'field_changed' => 'payment_status',
                'old_value' => $oldPaymentStatus,
                'new_value' => $newPaymentStatus,
                'description' => 'Actualizacion automatica del estado de pago tras cancelacion.',
                'created_at' => now(),
            ]);
        }

        $hydratedOrder = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);
        $this->notifyOrderCancellationChannels($hydratedOrder, $sourceConnection, $requiresRefund, $customerReason);

        return response()->json([
            'message' => $requiresRefund
                ? 'Pedido cancelado. Te enviamos un correo con el proceso de reembolso.'
                : 'Pedido cancelado correctamente.',
            'data' => [
                'order_id' => $id,
                'order_number' => $hydratedOrder->order_number ?? ('#' . $id),
                'status' => 'cancelled',
                'refund_required' => $requiresRefund,
                'payment_status' => $newPaymentStatus ?: $oldPaymentStatus,
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_email' => ['nullable', 'string', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['nullable', 'string'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $payload = [];

        $fieldMap = [
            'customer_name' => ['user_name', 'customer_name', 'billing_name'],
            'customer_email' => ['user_email', 'customer_email', 'billing_email'],
            'customer_phone' => ['user_phone', 'customer_phone', 'billing_phone', 'phone'],
            'shipping_address' => ['shipping_address'],
            'shipping_city' => ['shipping_city'],
            'payment_method' => ['payment_method'],
            'notes' => ['notes'],
        ];

        foreach ($fieldMap as $inputField => $candidates) {
            if (!$request->exists($inputField)) {
                continue;
            }

            $column = $this->firstExistingColumn('orders', $candidates, $sourceConnection);
            if (!$column) {
                continue;
            }

            $value = $data[$inputField] ?? null;
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    $value = null;
                }
            }

            $payload[$column] = $value;
        }

        if (empty($payload)) {
            return response()->json(['message' => 'No hay campos válidos para actualizar'], 422);
        }

        $payload['updated_at'] = now();
        $this->query($sourceConnection)->table('orders')->where('id', $id)->update($payload);

        $updatedOrder = $this->findOrderByConnection($sourceConnection, $id);
        if ($updatedOrder) {
            $updatedOrder = $this->hydrateOrderCustomerIdentity($updatedOrder, $sourceConnection);
        }

        return response()->json([
            'message' => 'Orden actualizada',
            'order' => $updatedOrder ?: $order,
        ]);
    }

    /**
     * Consulta órdenes por conexión (microservicio o legacy) manteniendo el contrato actual.
     */
    private function fetchOrdersByConnection(?string $connection, ?string $userId, ?string $status): Collection
    {
        try {
            $query = $this->query($connection)
                ->table('orders')
                ->select('orders.*')
                ->selectSub(function ($subQuery): void {
                    $subQuery
                        ->from('order_items')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('order_items.order_id', 'orders.id');
                }, 'items_count')
                ->orderByDesc('created_at');

            if ($userId !== null && $userId !== '') {
                $query->where('user_id', $userId);
            }

            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            return $query->limit(50)->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Combina resultados por múltiples candidatos de user_id y prioriza los más recientes.
     */
    private function fetchOrdersForCandidates(?string $connection, array $candidateUserIds, ?string $status): Collection
    {
        $orders = collect();

        foreach ($candidateUserIds as $candidateUserId) {
            $orders = $orders->concat($this->fetchOrdersByConnection($connection, $candidateUserId, $status));
        }

        return $orders
            ->unique('id')
            ->sortByDesc(function ($order): int {
                $raw = $order->created_at ?? null;
                $timestamp = is_string($raw) ? strtotime($raw) : null;
                return $timestamp ?: 0;
            })
            ->values()
            ->take(50)
            ->values();
    }

    private function findOrderByConnection(?string $connection, int $orderId): ?object
    {
        try {
            $order = $this->query($connection)
                ->table('orders')
                ->where('id', $orderId)
                ->first();

            if ($order) {
                $order->order_source = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';
            }

            return $order;
        } catch (Throwable) {
            return null;
        }
    }

    private function fetchOrderItemsByConnection(?string $connection, int $orderId): Collection
    {
        try {
            return $this->query($connection)
                ->table('order_items')
                ->where('order_id', $orderId)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function enrichOrderItemsWithCatalogImages(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $endpoint = $this->resolveCatalogProductImageEndpoint();
        if ($endpoint === null) {
            return $items;
        }

        $productIds = $items
            ->map(static fn ($item): int => (int) ($item->product_id ?? 0))
            ->filter(static fn ($productId): bool => $productId > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return $items;
        }

        $productImageById = [];

        foreach ($productIds as $productId) {
            try {
                $response = Http::acceptJson()
                    ->timeout(4)
                    ->get($endpoint . '/' . $productId);

                if (!$response->successful()) {
                    continue;
                }

                $payload = $response->json('data');
                if (!is_array($payload)) {
                    continue;
                }

                $resolvedPath = $this->pickCatalogProductImage($payload);
                if ($resolvedPath !== null) {
                    $productImageById[$productId] = $resolvedPath;
                }
            } catch (Throwable) {
                // El detalle de orden sigue operativo aunque falle la consulta de imágenes.
            }
        }

        if (empty($productImageById)) {
            return $items;
        }

        return $items->map(static function ($item) use ($productImageById) {
            $directImagePath = trim((string) ($item->product_image ?? $item->image ?? $item->image_path ?? ''));
            if ($directImagePath !== '') {
                $item->product_image = $directImagePath;
                return $item;
            }

            $productId = (int) ($item->product_id ?? 0);
            $catalogImagePath = $productImageById[$productId] ?? null;
            if ($catalogImagePath !== null && $catalogImagePath !== '') {
                $item->product_image = $catalogImagePath;
            }

            return $item;
        })->values();
    }

    private function resolveCatalogProductImageEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.catalog.base_url', 'http://catalog-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/internal/products';
        }

        return $baseUrl . '/api/internal/products';
    }

    private function pickCatalogProductImage(array $payload): ?string
    {
        $candidates = [
            $payload['primary_image'] ?? null,
            $payload['image_path'] ?? null,
            $payload['image'] ?? null,
            $payload['image_url'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->nullableString($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function fetchOrderHistoryByConnection(?string $connection, int $orderId): Collection
    {
        try {
            if (!$this->hasTable('order_status_history', $connection)) {
                return collect();
            }

            return $this->query($connection)
                ->table('order_status_history')
                ->where('order_id', $orderId)
                ->orderByDesc('created_at')
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function hydrateOrderCustomerIdentity(object $order, ?string $sourceConnection): object
    {
        $currentName = trim((string) ($order->user_name ?? $order->customer_name ?? $order->billing_name ?? ''));
        $currentEmail = trim((string) ($order->user_email ?? $order->customer_email ?? $order->billing_email ?? ''));
        $currentPhone = trim((string) ($order->user_phone ?? $order->customer_phone ?? $order->billing_phone ?? $order->phone ?? ''));

        if ($currentName !== '' && $currentEmail !== '' && $currentPhone !== '') {
            return $order;
        }

        $userId = trim((string) ($order->user_id ?? ''));
        if ($userId === '') {
            return $order;
        }

        $user = $this->findUserById($sourceConnection, $userId);
        if (!$user && $sourceConnection !== self::LEGACY_CONNECTION) {
            $user = $this->findUserById(self::LEGACY_CONNECTION, $userId);
        }

        if (!$user) {
            return $order;
        }

        if ($currentName === '') {
            $order->user_name = $user->name ?? null;
        }

        if ($currentEmail === '') {
            $order->user_email = $user->email ?? null;
        }

        if ($currentPhone === '') {
            $order->user_phone = $user->phone ?? null;
        }

        return $order;
    }

    private function findUserById(?string $connection, string $userId): ?object
    {
        try {
            if (!$this->hasTable('users', $connection)) {
                return null;
            }

            return $this->query($connection)
                ->table('users')
                ->select('id', 'name', 'email', 'phone')
                ->where('id', $userId)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Construye candidatos de user_id usando id directo y resolución por correo en legacy.
     */
    private function buildCandidateUserIds(?string $userId, ?string $userEmail): array
    {
        $candidateUserIds = [];

        if ($userId !== null && $userId !== '') {
            $candidateUserIds[] = $userId;
        }

        $legacyUserId = $this->resolveLegacyUserIdByEmail($userEmail);

        if ($legacyUserId !== null && !in_array($legacyUserId, $candidateUserIds, true)) {
            $candidateUserIds[] = $legacyUserId;
        }

        return $candidateUserIds;
    }

    /**
     * Resuelve id de usuario legacy por correo para mantener compatibilidad de datos.
     */
    private function resolveLegacyUserIdByEmail(?string $userEmail): ?string
    {
        if ($userEmail === null || $userEmail === '') {
            return null;
        }

        try {
            $userId = DB::connection(self::LEGACY_CONNECTION)
                ->table('users')
                ->whereRaw('LOWER(email) = ?', [Str::lower($userEmail)])
                ->value('id');

            if ($userId === null || $userId === '') {
                return null;
            }

            return (string) $userId;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Obtiene el query builder según conexión.
     * `null` usa la conexión por defecto del microservicio.
     */
    private function query(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }

    private function resolveOrderSource(int $orderId, ?string $preferredConnection = null): array
    {
        $connections = $preferredConnection === self::LEGACY_CONNECTION
            ? [self::LEGACY_CONNECTION, null]
            : [null, self::LEGACY_CONNECTION];

        foreach ($connections as $connection) {
            $order = $this->findOrderByConnection($connection, $orderId);
            if ($order) {
                return ['order' => $order, 'connection' => $connection];
            }
        }

        return ['order' => null, 'connection' => null];
    }

    private function normalizeSourceConnection(mixed $source): ?string
    {
        $value = strtolower(trim((string) ($source ?? '')));

        if (in_array($value, ['legacy', 'legacy_mysql'], true)) {
            return self::LEGACY_CONNECTION;
        }

        return null;
    }

    private function resolveConnectionName(?string $connection): string
    {
        return $connection ?: config('database.default');
    }

    private function hasTable(string $table, ?string $connection): bool
    {
        return Schema::connection($this->resolveConnectionName($connection))->hasTable($table);
    }

    private function firstExistingColumn(string $table, array $candidates, ?string $connection): ?string
    {
        $connectionName = $this->resolveConnectionName($connection);
        foreach ($candidates as $column) {
            if (Schema::connection($connectionName)->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function insertOrderHistory(?string $connection, array $payload): void
    {
        try {
            if (!$this->hasTable('order_status_history', $connection)) {
                return;
            }

            $this->query($connection)->table('order_status_history')->insert($payload);
        } catch (Throwable) {
            // Silenciar para no romper acciones de administración por entornos sin historial.
        }
    }

    private function canCustomerManageOrder(object $order, ?string $sourceConnection, ?string $userId, ?string $userEmail): bool
    {
        $normalizedUserEmail = $this->nullableString($userEmail);
        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($userId),
            $normalizedUserEmail,
        );

        $orderUserId = trim((string) ($order->user_id ?? ''));
        if (!empty($candidateUserIds) && $orderUserId !== '') {
            if (in_array($orderUserId, $candidateUserIds, true)) {
                return true;
            }
        }

        if ($normalizedUserEmail !== null) {
            $orderEmail = $this->resolveOrderCustomerEmail($order, $sourceConnection);
            if ($orderEmail !== null && Str::lower($orderEmail) === Str::lower($normalizedUserEmail)) {
                return true;
            }
        }

        return false;
    }

    private function isCustomerCancelableStatus(?string $status): bool
    {
        $normalized = Str::lower(trim((string) ($status ?? '')));

        return in_array($normalized, ['pending', 'processing', 'confirmed', 'paid'], true);
    }

    private function requiresRefundOnCancel(?string $paymentStatus, ?string $orderStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        if (in_array($normalizedPaymentStatus, ['paid', 'approved', 'verified'], true)) {
            return true;
        }

        $normalizedOrderStatus = Str::lower(trim((string) ($orderStatus ?? '')));
        return $normalizedPaymentStatus === '' && in_array($normalizedOrderStatus, ['paid', 'confirmed'], true);
    }

    private function shouldMoveToPendingRefund(?string $targetStatus, ?string $paymentStatus, ?string $orderStatus): bool
    {
        if (
            !$this->sameNormalizedValue($targetStatus, 'cancelled')
            && !$this->sameNormalizedValue($targetStatus, 'canceled')
        ) {
            return false;
        }

        if (!$this->requiresRefundOnCancel($paymentStatus, $orderStatus)) {
            return false;
        }

        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        return !in_array($normalizedPaymentStatus, ['pending_refund', 'refunded'], true);
    }

    private function supportsReservationWorkflow(?string $sourceConnection): bool
    {
        return $sourceConnection !== self::LEGACY_CONNECTION;
    }

    private function shouldMoveOrderToReview(?string $status): bool
    {
        $normalizedStatus = Str::lower(trim((string) ($status ?? '')));
        return in_array($normalizedStatus, self::REVIEWABLE_ORDER_STATUSES, true);
    }

    private function shouldConfirmReservationForOrderStatus(?string $status): bool
    {
        $normalizedStatus = Str::lower(trim((string) ($status ?? '')));
        return in_array($normalizedStatus, self::CONFIRM_RESERVATION_STATUS_VALUES, true);
    }

    private function shouldReleaseReservationForOrderStatus(?string $status): bool
    {
        $normalizedStatus = Str::lower(trim((string) ($status ?? '')));
        return in_array($normalizedStatus, self::RELEASE_RESERVATION_STATUS_VALUES, true);
    }

    private function shouldConfirmReservationForPaymentStatus(?string $paymentStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        return in_array($normalizedPaymentStatus, self::CONFIRM_RESERVATION_PAYMENT_VALUES, true);
    }

    private function shouldReleaseReservationForPaymentStatus(?string $paymentStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        return in_array($normalizedPaymentStatus, self::RELEASE_RESERVATION_PAYMENT_VALUES, true);
    }

    private function shouldAutoCancelOrderForPaymentStatus(?string $paymentStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        return in_array($normalizedPaymentStatus, ['rejected', 'failed', 'cancelled', 'canceled'], true);
    }

    private function cancelOrderByInventoryConflict(
        int $orderId,
        object $order,
        ?string $sourceConnection,
        ?string $changedBy,
        ?string $changedByName,
    ): void {
        if (!$this->supportsReservationWorkflow($sourceConnection)) {
            return;
        }

        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $sourceConnection);
        if ($statusColumn === null) {
            return;
        }

        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $sourceConnection);
        $currentStatus = $order->{$statusColumn} ?? $order->status ?? $order->order_status ?? null;
        $currentPaymentStatus = $paymentStatusColumn
            ? ($order->{$paymentStatusColumn} ?? $order->payment_status ?? null)
            : ($order->payment_status ?? null);

        if ($this->sameNormalizedValue($currentStatus, 'cancelled') || $this->sameNormalizedValue($currentStatus, 'canceled')) {
            return;
        }

        $updatePayload = [
            $statusColumn => 'cancelled',
            'updated_at' => now(),
        ];

        if ($paymentStatusColumn !== null && !$this->sameNormalizedValue($currentPaymentStatus, 'failed')) {
            $updatePayload[$paymentStatusColumn] = 'failed';
        }

        $this->query($sourceConnection)->table('orders')->where('id', $orderId)->update($updatePayload);

        $this->insertOrderHistory($sourceConnection, [
            'order_id' => $orderId,
            'changed_by' => $this->nullableString($changedBy),
            'changed_by_name' => $this->nullableString($changedByName),
            'change_type' => 'status_change',
            'field_changed' => 'status',
            'old_value' => $currentStatus,
            'new_value' => 'cancelled',
            'description' => 'Orden cancelada automaticamente por falta de inventario al validar pago.',
            'created_at' => now(),
        ]);

        if ($paymentStatusColumn !== null && !$this->sameNormalizedValue($currentPaymentStatus, 'failed')) {
            $this->insertOrderHistory($sourceConnection, [
                'order_id' => $orderId,
                'changed_by' => $this->nullableString($changedBy),
                'changed_by_name' => $this->nullableString($changedByName),
                'change_type' => 'payment_change',
                'field_changed' => 'payment_status',
                'old_value' => $currentPaymentStatus,
                'new_value' => 'failed',
                'description' => 'Pago rechazado automaticamente porque la reserva expiro y no habia stock disponible.',
                'created_at' => now(),
            ]);
        }

        $order->status = 'cancelled';
        if ($paymentStatusColumn !== null) {
            $order->payment_status = 'failed';
        }

        $hydratedOrder = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);
        $this->notifyOrderCancellationChannels(
            $hydratedOrder,
            $sourceConnection,
            false,
            'No habia stock disponible al momento de validar el pago.',
        );
    }

    private function notifyOrderCreationChannels(object $order, ?string $sourceConnection): void
    {
        $orderId = (int) ($order->id ?? 0);
        $orderLabel = trim((string) ($order->order_number ?? ''));
        if ($orderLabel === '') {
            $orderLabel = $orderId > 0 ? '#' . $orderId : 'N/A';
        }

        $title = 'Recibimos tu pedido';
        $message = "Tu pedido {$orderLabel} fue creado correctamente y está pendiente de verificación de pago.";
        $userId = trim((string) ($order->user_id ?? ''));

        if ($userId !== '') {
            $this->sendOrderNotification($userId, $orderId, $title, $message);
        }

        $customerEmail = $this->resolveOrderCustomerEmail($order, $sourceConnection);
        if ($customerEmail === null) {
            return;
        }

        $customerName = $this->resolveOrderCustomerName($order, $sourceConnection);
        $subject = "Pedido {$orderLabel} recibido";
        $this->sendOrderUpdateEmail($customerEmail, $customerName, $subject, $title, $message, $orderLabel);
    }

    private function notifyOrderCancellationChannels(object $order, ?string $sourceConnection, bool $requiresRefund, ?string $reason): void
    {
        $orderId = (int) ($order->id ?? 0);
        $orderLabel = trim((string) ($order->order_number ?? ''));
        if ($orderLabel === '') {
            $orderLabel = $orderId > 0 ? '#' . $orderId : 'N/A';
        }

        $title = 'Tu pedido fue cancelado';
        $message = $this->buildOrderCancellationMessage($orderLabel, $requiresRefund, $reason);
        $userId = trim((string) ($order->user_id ?? ''));

        if ($userId !== '') {
            $this->sendOrderNotification($userId, $orderId, $title, $message);
        }

        $customerEmail = $this->resolveOrderCustomerEmail($order, $sourceConnection);
        if ($customerEmail !== null) {
            $customerName = $this->resolveOrderCustomerName($order, $sourceConnection);
            $subject = $requiresRefund
                ? "Pedido {$orderLabel} cancelado - Reembolso en proceso"
                : "Pedido {$orderLabel} cancelado";

            $this->sendOrderUpdateEmail($customerEmail, $customerName, $subject, $title, $message, $orderLabel);
        }

        if ($requiresRefund) {
            $this->sendRefundTeamEmail($order, $sourceConnection, $orderLabel, $reason);
        }
    }

    private function buildOrderCancellationMessage(string $orderLabel, bool $requiresRefund, ?string $reason): string
    {
        $message = $requiresRefund
            ? "Tu pedido {$orderLabel} fue cancelado y el reembolso quedó en proceso. Nuestro equipo financiero te confirmará por correo cuando se complete."
            : "Tu pedido {$orderLabel} fue cancelado correctamente.";

        $normalizedReason = $this->nullableString($reason);
        if ($normalizedReason !== null) {
            $message .= " Motivo registrado: {$normalizedReason}.";
        }

        return $message;
    }

    private function sendRefundTeamEmail(object $order, ?string $sourceConnection, string $orderLabel, ?string $reason): bool
    {
        $teamEmail = $this->nullableString(config('services.refunds.team_email'));
        if ($teamEmail === null || !filter_var($teamEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $customerName = $this->resolveOrderCustomerName($order, $sourceConnection);
        $customerEmail = $this->resolveOrderCustomerEmail($order, $sourceConnection) ?? 'Sin correo';
        $paymentStatus = trim((string) ($order->payment_status ?? 'pending_refund'));
        $status = trim((string) ($order->status ?? $order->order_status ?? 'cancelled'));
        $total = (float) ($order->total ?? 0);

        try {
            $html = $this->buildRefundTeamEmailHtml(
                $orderLabel,
                $customerName,
                $customerEmail,
                $status,
                $paymentStatus,
                $total,
                $reason,
            );

            Mail::html($html, static function ($mail) use ($teamEmail, $orderLabel): void {
                $mail->to($teamEmail)->subject("Solicitud de reembolso {$orderLabel}");
            });

            return true;
        } catch (Throwable $exception) {
            Log::warning('No se pudo notificar al equipo de reembolsos.', [
                'order_id' => $order->id ?? null,
                'order_number' => $orderLabel,
                'team_email' => $teamEmail,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function buildRefundTeamEmailHtml(
        string $orderLabel,
        string $customerName,
        string $customerEmail,
        string $status,
        string $paymentStatus,
        float $total,
        ?string $reason,
    ): string {
        $safeOrderLabel = e($orderLabel);
        $safeCustomerName = e($customerName);
        $safeCustomerEmail = e($customerEmail);
        $safeStatus = e($this->normalizeOperationalLabel($status, 'status') ?? $status);
        $safePaymentStatus = e($this->normalizeOperationalLabel($paymentStatus, 'payment_status') ?? $paymentStatus);
        $safeReason = e($this->nullableString($reason) ?? 'No especificado');
        $safeTotal = e(number_format($total, 0, ',', '.'));

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de reembolso {$safeOrderLabel}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f8fb; color: #1f2937; padding: 20px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #dbe3ed; border-radius: 10px; overflow: hidden; }
        .header { background: #0f7abf; color: #fff; padding: 16px 20px; font-weight: 700; }
        .content { padding: 18px 20px; }
        .row { margin-bottom: 10px; }
        .label { color: #6b7280; font-size: 12px; display: block; text-transform: uppercase; letter-spacing: 0.04em; }
        .value { font-size: 15px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">Solicitud de reembolso - {$safeOrderLabel}</div>
        <div class="content">
            <div class="row"><span class="label">Cliente</span><span class="value">{$safeCustomerName}</span></div>
            <div class="row"><span class="label">Correo</span><span class="value">{$safeCustomerEmail}</span></div>
            <div class="row"><span class="label">Estado de orden</span><span class="value">{$safeStatus}</span></div>
            <div class="row"><span class="label">Estado de pago</span><span class="value">{$safePaymentStatus}</span></div>
            <div class="row"><span class="label">Total</span><span class="value">
                {$safeTotal} COP
            </span></div>
            <div class="row"><span class="label">Motivo</span><span class="value">{$safeReason}</span></div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function notifyOrderUpdateChannels(object $order, ?string $sourceConnection, string $field, ?string $oldValue, ?string $newValue): void
    {
        $normalizedNewValue = $this->nullableString($newValue);
        if ($normalizedNewValue === null) {
            return;
        }

        $orderId = (int) ($order->id ?? 0);
        $orderLabel = trim((string) ($order->order_number ?? ''));
        if ($orderLabel === '') {
            $orderLabel = $orderId > 0 ? '#' . $orderId : 'N/A';
        }

        $title = $field === 'payment_status'
            ? 'Actualización de pago de tu orden'
            : 'Actualización de estado de tu orden';

        $message = $this->buildOrderUpdateMessage($field, $orderLabel, $oldValue, $normalizedNewValue);
        $userId = trim((string) ($order->user_id ?? ''));

        if ($userId !== '') {
            $this->sendOrderNotification($userId, $orderId, $title, $message);
        }

        $customerEmail = $this->resolveOrderCustomerEmail($order, $sourceConnection);
        if ($customerEmail === null) {
            return;
        }

        $customerName = $this->resolveOrderCustomerName($order, $sourceConnection);
        $subject = $field === 'payment_status'
            ? "Actualización del pago de tu orden {$orderLabel}"
            : "Actualización de estado de tu orden {$orderLabel}";

        $this->sendOrderUpdateEmail($customerEmail, $customerName, $subject, $title, $message, $orderLabel);
    }

    private function buildOrderUpdateMessage(string $field, string $orderLabel, ?string $oldValue, string $newValue): string
    {
        $oldLabel = $this->normalizeOperationalLabel($oldValue, $field);
        $newLabel = $this->normalizeOperationalLabel($newValue, $field) ?? 'Actualizado';

        if ($field === 'payment_status') {
            if ($oldLabel === null) {
                return "El estado de pago de la orden {$orderLabel} ahora es {$newLabel}.";
            }

            return "El estado de pago de la orden {$orderLabel} cambió de {$oldLabel} a {$newLabel}.";
        }

        if ($oldLabel === null) {
            return "La orden {$orderLabel} ahora está en estado {$newLabel}.";
        }

        return "La orden {$orderLabel} cambió de estado: {$oldLabel} a {$newLabel}.";
    }

    private function normalizeOperationalLabel(?string $value, string $field): ?string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return null;
        }

        $statusMap = [
            'pending' => 'Pendiente',
            'in_review' => 'En revision',
            'en_revision' => 'En revision',
            'processing' => 'En preparación',
            'in_process' => 'En preparación',
            'confirmed' => 'Confirmado',
            'paid' => 'Pagado',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'completed' => 'Completado',
            'expired' => 'Expirado',
            'cancelled' => 'Cancelado',
            'canceled' => 'Cancelado',
            'returned' => 'Devuelto',
            'failed' => 'Fallido',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'on_hold' => 'En espera',
        ];

        $paymentMap = [
            'pending' => 'Pendiente',
            'pending_payment' => 'Pago pendiente',
            'pending_refund' => 'Reembolso en proceso',
            'paid' => 'Pagado',
            'approved' => 'Aprobado',
            'verified' => 'Verificado',
            'rejected' => 'Rechazado',
            'failed' => 'Fallido',
            'cancelled' => 'Cancelado',
            'canceled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'partial' => 'Pago parcial',
            'partially_paid' => 'Pago parcial',
            'unpaid' => 'Sin pagar',
            'transfer' => 'Transferencia',
        ];

        $map = $field === 'payment_status' ? $paymentMap : $statusMap;
        if (array_key_exists($normalized, $map)) {
            return $map[$normalized];
        }

        return Str::title(str_replace(['_', '-'], ' ', $normalized));
    }

    private function sendOrderNotification(string $userId, int $orderId, string $title, string $message): bool
    {
        $endpoint = $this->resolveNotificationEndpoint();
        if ($endpoint === null) {
            return false;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->post($endpoint, [
                    'user_id' => $userId,
                    'type_id' => (int) config('services.notifications.order_type_id', self::DEFAULT_NOTIFICATION_TYPE_ID),
                    'title' => $title,
                    'message' => $message,
                    'related_entity_type' => 'order',
                    'related_entity_id' => $orderId > 0 ? $orderId : null,
                ]);

            if (!$response->successful()) {
                Log::warning('No se pudo registrar notificación de orden.', [
                    'user_id' => $userId,
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (Throwable $exception) {
            Log::warning('Fallo enviando notificación de orden a notification-service.', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendOrderUpdateEmail(string $email, string $customerName, string $subject, string $title, string $message, string $orderLabel): bool
    {
        try {
            $html = $this->buildOrderUpdateEmailHtml($customerName, $title, $message, $orderLabel);

            Mail::html($html, function ($mail) use ($email, $customerName, $subject): void {
                $mail->to($email, $customerName)->subject($subject);
            });

            return true;
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar correo de actualización de orden.', [
                'email' => $email,
                'subject' => $subject,
                'mailer' => (string) config('mail.default', 'log'),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function buildOrderUpdateEmailHtml(string $customerName, string $title, string $message, string $orderLabel): string
    {
        $safeName = e($customerName);
        $safeTitle = e($title);
        $safeMessage = e($message);
        $safeOrderLabel = e($orderLabel);
        $safeGeneratedAt = e(Carbon::now('America/Bogota')->format('d/m/Y H:i'));

        $storeUrl = trim((string) config('services.frontend.store_url', 'http://localhost:5173'));
        if ($storeUrl === '') {
            $storeUrl = 'http://localhost:5173';
        }
        $ordersUrl = rtrim($storeUrl, '/') . '/mi-cuenta/pedidos';
        $safeOrdersUrl = e($ordersUrl);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$safeTitle}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background: #f5f7fb; margin: 0; padding: 20px 0; }
        .container { max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 10px; overflow: hidden; }
        .header { background: #0f7abf; color: #ffffff; padding: 20px 24px; }
        .header h1 { margin: 0; font-size: 20px; }
        .content { padding: 22px 24px; }
        .order-chip { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #e7f3fb; color: #0f7abf; font-weight: 700; margin: 8px 0 16px; }
        .message { margin: 0 0 16px; font-size: 15px; }
        .cta { margin-top: 16px; }
        .cta a { display: inline-block; background: #0f7abf; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; }
        .footer { padding: 14px 24px; border-top: 1px solid #e5edf6; color: #667085; font-size: 12px; background: #fafcff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$safeTitle}</h1>
        </div>
        <div class="content">
            <p>Hola {$safeName},</p>
            <span class="order-chip">Orden {$safeOrderLabel}</span>
            <p class="message">{$safeMessage}</p>
            <p>Revisa el detalle completo de tu pedido desde tu cuenta.</p>
            <p class="cta">
                <a href="{$safeOrdersUrl}">Ver mis pedidos</a>
            </p>
        </div>
        <div class="footer">
            Generado el {$safeGeneratedAt} (hora Colombia) · Angelow
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function resolveOrderCustomerEmail(object $order, ?string $sourceConnection): ?string
    {
        $candidates = [
            trim((string) ($order->user_email ?? '')),
            trim((string) ($order->customer_email ?? '')),
            trim((string) ($order->billing_email ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        $userId = trim((string) ($order->user_id ?? ''));
        if ($userId === '') {
            return null;
        }

        $user = $this->findUserById($sourceConnection, $userId);
        if (!$user && $sourceConnection !== self::LEGACY_CONNECTION) {
            $user = $this->findUserById(self::LEGACY_CONNECTION, $userId);
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function resolveOrderCustomerName(object $order, ?string $sourceConnection): string
    {
        $candidates = [
            trim((string) ($order->user_name ?? '')),
            trim((string) ($order->customer_name ?? '')),
            trim((string) ($order->billing_name ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $userId = trim((string) ($order->user_id ?? ''));
        if ($userId !== '') {
            $user = $this->findUserById($sourceConnection, $userId);
            if (!$user && $sourceConnection !== self::LEGACY_CONNECTION) {
                $user = $this->findUserById(self::LEGACY_CONNECTION, $userId);
            }

            $resolvedName = trim((string) ($user->name ?? ''));
            if ($resolvedName !== '') {
                return $resolvedName;
            }
        }

        return 'Cliente';
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

    private function sameNormalizedValue(?string $left, ?string $right): bool
    {
        return Str::lower(trim((string) ($left ?? ''))) === Str::lower(trim((string) ($right ?? '')));
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
