<?php

namespace App\Http\Controllers;

use App\Jobs\ExpireStockReservationJob;
use App\Services\OrderInvoiceService;
use App\Services\StockReservationRealtimePublisher;
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
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends Controller
{
    // Conexión legacy usada como respaldo durante la migración.
    private const LEGACY_CONNECTION = 'legacy_mysql';

    // ID por defecto para notificaciones de tipo orden.
    private const DEFAULT_NOTIFICATION_TYPE_ID = 1;

    // Estados que pueden pasar a revisión al recibir comprobante de pago.
    private const REVIEWABLE_ORDER_STATUSES = ['pending', 'pending_payment', 'created'];

    // Estado destino cuando se recibe el comprobante y se inicia la verificación.
    private const REVIEW_STATUS = 'processing';

    // Lista completa de estados de orden permitidos para actualización manual.
    private const ALLOWED_ORDER_STATUS_VALUES = ['created', 'pending', 'pending_payment', 'in_review', 'en_revision', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'canceled', 'refunded'];
    // Estados de orden que confirman la reserva de inventario.
    private const CONFIRM_RESERVATION_STATUS_VALUES = ['paid', 'confirmed', 'processing', 'completed', 'delivered'];

    // Estados de orden que liberan la reserva de inventario.
    private const RELEASE_RESERVATION_STATUS_VALUES = ['cancelled', 'canceled', 'rejected', 'failed'];

    // Estados de pago que confirman la reserva de inventario.
    private const CONFIRM_RESERVATION_PAYMENT_VALUES = ['paid', 'approved', 'verified'];

    // Estados de pago que liberan la reserva de inventario.
    private const RELEASE_RESERVATION_PAYMENT_VALUES = ['rejected', 'failed', 'cancelled', 'canceled'];

    // Caché local de perfiles de auth-service para evitar consultas repetidas.
    private array $authProfilesById = [];

    /**
     * Inyecta los servicios necesarios para operaciones de orden,
     * facturación y reserva de inventario.
     */
    public function __construct(
        private readonly OrderInvoiceService $orderInvoiceService,
        private readonly StockReservationService $stockReservationService,
        private readonly StockReservationRealtimePublisher $stockRealtimePublisher,
    ) {}

    /**
     * Lista órdenes del usuario. Soporta filtro por status y búsqueda
     * por user_id o user_email. Hace fallback a legacy si no hay datos locales.
     */
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

        // Retorna los resultados combinados (microservicio + legacy si aplica).

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Muestra el detalle completo de una orden: datos del cliente,
     * productos con imágenes y el historial de cambios de estado.
     * Útil para que administradores y clientes vean la información
     * completa de un pedido.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $preferredConnection = $this->normalizeSourceConnection($request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        // Enriquece la orden con datos del usuario (nombre, email, teléfono).
        $order = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);

        // Obtiene los productos de la orden y les asigna imágenes desde el catálogo.
        $items = $this->fetchOrderItemsByConnection($sourceConnection, $id);
        $items = $this->enrichOrderItemsWithCatalogImages($items);
        $history = $this->fetchOrderHistoryByConnection($sourceConnection, $id);

        return response()->json([
            'order' => $order,
            'items' => $items,
            'history' => $history,
        ]);
    }

    /**
     * Descarga la factura en PDF de una orden, solo si el cliente tiene permisos.
     */
    public function downloadInvoice(Request $request, int $id)
    {
        // Reutiliza la generación de factura existente, pero valida primero que el pedido pertenezca al cliente.
        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:20'],
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($data['source'] ?? $request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $order = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);
        if (!$this->canCustomerManageOrder($order, $sourceConnection, $data['user_id'] ?? null, $data['user_email'] ?? null)) {
            return response()->json(['message' => 'No tienes permiso para descargar esta factura.'], 403);
        }

        $result = $this->orderInvoiceService->buildInvoicePdfForDownload($id, $sourceConnection);
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'No se pudo descargar la factura.',
            ], (int) ($result['code'] ?? 422));
        }

        return response($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . ($result['filename'] ?? ('factura_' . $id . '.pdf')) . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Crea una nueva orden con sus ítems asociados, realiza la
     * reserva de inventario y programa la expiración de la misma.
     * Si la orden ya existe (duplicada), retorna el ID existente.
     */
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

        // Verifica duplicados por número de orden antes de crear.
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
            // Construye el payload base de la orden con campos siempre presentes.
            $orderPayload = [
                'order_number' => $data['order_number'],
                'user_id' => $data['user_id'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'subtotal' => $data['subtotal'],
                'total' => $data['total'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Agrega descuento solo si la columna existe (compatibilidad entre esquemas).
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

            // Itera campos opcionales incluyéndolos solo si existen en la tabla y tienen valor.
            foreach ($optionalOrderFields as $column => $value) {
                if (!Schema::hasColumn($ordersTable, $column)) {
                    continue;
                }

                if ($value !== null && $value !== '') {
                    $orderPayload[$column] = $value;
                }
            }

            $id = (int) DB::table('orders')->insertGetId($orderPayload);

            // Inserta los ítems de la orden si la tabla existe.
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

            // Reserva inventario (TTL configurable, por defecto 2 horas).
            $reservationTtl = (int) config('services.stock_reservations.ttl_seconds', 7200);
            $reservationResult = $this->stockReservationService->reserveForOrder(
                orderId: $id,
                orderNumber: (string) $data['order_number'],
                items: $data['items'],
                ttlSeconds: $reservationTtl,
            );

            // Si la reserva falla, revierte toda la transacción.
            if (!($reservationResult['ok'] ?? false)) {
                DB::rollBack();

                return response()->json([
                    'message' => $reservationResult['message'] ?? 'No fue posible reservar inventario para crear la orden.',
                    'code' => $reservationResult['code'] ?? 'reservation_failed',
                ], (int) ($reservationResult['http_status'] ?? 422));
            }

            DB::commit();
        } catch (Throwable $throwable) {
            // Reversión completa ante cualquier excepción no manejada.
            DB::rollBack();
            throw $throwable;
        }

        // Programa la expiración automática de la reserva si el servicio devolvió una fecha.
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

    /**
     * Envía confirmación de checkout al cliente y,
     * si el flujo de reserva está activo, mueve la orden
     * a revisión y extiende la reserva de inventario.
     */
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

        // Si el origen soporta el flujo de reservas, procesa transición a revisión.
        if ($this->supportsReservationWorkflow($sourceConnection)) {
            $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $sourceConnection);
            $oldStatus = $statusColumn
                ? ($order->{$statusColumn} ?? $order->status ?? $order->order_status ?? null)
                : ($order->status ?? $order->order_status ?? null);

            // Cambia el estado a "en revisión" si la orden está en un estado reviewable.
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
                    'description' => 'Comprobante recibido. La orden pasó a proceso mientras se verifica el pago.',
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

            // Extiende la reserva de stock para dar tiempo a la verificación del pago.
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

    /**
     * Actualiza el estado de una orden, gestionando reservas de
     * inventario (confirmar o liberar según el destino) y generando
     * factura si corresponde. También maneja transiciones automáticas
     * de estado de pago hacia pending_refund.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:20', Rule::in(self::ALLOWED_ORDER_STATUS_VALUES)],
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

        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $sourceConnection);
        $oldStatus = $order->{$statusColumn} ?? $order->status ?? $order->order_status ?? null;
        $oldPaymentStatus = $paymentStatusColumn
            ? ($order->{$paymentStatusColumn} ?? $order->payment_status ?? null)
            : ($order->payment_status ?? null);
        // Determina si debe pasar a pendiente de reembolso cuando se cancela con pago previo.
        $shouldMoveToPendingRefund = $paymentStatusColumn !== null
            && $this->shouldMoveToPendingRefund($data['status'], $oldPaymentStatus, $oldStatus);
        $newPaymentStatus = $shouldMoveToPendingRefund ? 'pending_refund' : null;
        $targetStatus = (string) $data['status'];

        // Gestiona reserva de inventario según el estado destino.
        if ($this->supportsReservationWorkflow($sourceConnection)) {
            // Confirma la reserva si el nuevo estado requiere inventario asegurado.
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
            // Libera la reserva si el nuevo estado es de cancelación o rechazo.
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

        // Persiste los cambios de estado en la BD del origen correspondiente.
        $this->query($sourceConnection)->table('orders')->where('id', $id)->update($updatePayload);

        // Registra el cambio en el historial de estados de la orden.
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
            // Registra el cambio automático de estado de pago en el historial.
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

        // Notifica del cambio de estado si realmente hubo modificación.
        if (!$this->sameNormalizedValue($oldStatus, $data['status'])) {
            $this->notifyOrderUpdateChannels(
                $hydratedOrder,
                $sourceConnection,
                'status',
                $oldStatus,
                $data['status'],
            );
        }

        // Notifica del cambio de pago si aplica (todavía no está en sameNormalizedValue).
        if ($newPaymentStatus !== null && !$this->sameNormalizedValue($oldPaymentStatus, $newPaymentStatus)) {
            $this->notifyOrderUpdateChannels(
                $hydratedOrder,
                $sourceConnection,
                'payment_status',
                $oldPaymentStatus,
                $newPaymentStatus,
            );
        }

        // Verifica si debe generar factura para órdenes completadas (lo delega al servicio).
        $invoiceResult = $this->orderInvoiceService->ensureInvoiceForCompletedOrder($id, $sourceConnection);

        return response()->json([
            'message' => 'Estado actualizado',
            'invoice' => $invoiceResult,
        ]);
    }

    /**
     * Actualiza el estado de pago de una orden, con manejo de
     * confirmación/liberación de reserva de inventario, cancelación
     * automática por falta de stock, y notificaciones al cliente.
     */
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
        if ($this->shouldMoveVerifiedPaymentToRefund($oldStatus, $targetPaymentStatus)) {
            $targetPaymentStatus = 'pending_refund';
        }
        $paymentStatusChanged = !$this->sameNormalizedValue($oldPaymentStatus, $targetPaymentStatus);

        if ($paymentStatusChanged && $this->supportsReservationWorkflow($sourceConnection)) {
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
            'description' => $data['description'] ?? $this->defaultPaymentStatusChangeDescription($oldStatus, $data['payment_status'], $targetPaymentStatus),
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

    /**
     * Desactiva (cancela) una orden desde administración.
     * Libera la reserva de inventario, registra historial y
     * notifica al cliente del cambio.
     */
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

    /**
     * Cancela una orden desde el frontend del cliente.
     * Valida permisos, libera inventario, y maneja la
     * lógica de reembolso si el pedido ya fue pagado.
     */
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

        // Verifica que el cliente sea el propietario de la orden antes de cancelar.
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
        // Previene doble cancelación.
        if ($this->sameNormalizedValue($oldStatus, 'cancelled') || $this->sameNormalizedValue($oldStatus, 'canceled')) {
            return response()->json([
                'message' => 'La orden ya se encuentra cancelada.',
            ], 422);
        }

        // Restringe cancelación solo a estados permitidos para el cliente.
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

    /**
     * Registra una solicitud de reembolso del cliente sin cancelar la orden entregada.
     */
    public function requestRefund(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:20'],
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'reason' => ['required', 'string', 'max:80'],
            'details' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($data['source'] ?? $request->input('source'));
        ['order' => $order, 'connection' => $sourceConnection] = $this->resolveOrderSource($id, $preferredConnection);

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        // Reutiliza la autorización de gestión de pedido usada por factura y cancelación de cliente.
        if (!$this->canCustomerManageOrder($order, $sourceConnection, $data['user_id'] ?? null, $data['user_email'] ?? null)) {
            return response()->json(['message' => 'No tienes permisos para solicitar reembolso de esta orden.'], 403);
        }

        $eligibility = $this->resolveOrderRefundEligibility($order, $sourceConnection);
        if (!($eligibility['available'] ?? false)) {
            return response()->json([
                'message' => $eligibility['message'] ?? 'Esta orden no tiene reembolso disponible.',
                'data' => $eligibility,
            ], 422);
        }

        if ($this->existingOpenRefundRequest($sourceConnection, $id) !== null) {
            return response()->json(['message' => 'Ya existe una solicitud de reembolso en revisión para este pedido.'], 422);
        }

        $evidencePath = $this->storeRefundEvidence($request->file('evidence'));
        $now = now();

        $requestId = $this->query($sourceConnection)->table('order_refund_requests')->insertGetId([
            'order_id' => $id,
            'user_id' => $this->nullableString($data['user_id'] ?? null),
            'user_email' => $this->nullableString($data['user_email'] ?? null),
            'reason' => trim((string) $data['reason']),
            'details' => $this->nullableString($data['details'] ?? null),
            'evidence_path' => $evidencePath,
            'evidence_original_name' => $request->file('evidence')?->getClientOriginalName(),
            'status' => 'requested',
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $sourceConnection);
        $oldPaymentStatus = $paymentStatusColumn ? ($order->{$paymentStatusColumn} ?? $order->payment_status ?? null) : null;
        if ($paymentStatusColumn && !$this->sameNormalizedValue($oldPaymentStatus, 'refund_requested')) {
            $this->query($sourceConnection)->table('orders')->where('id', $id)->update([
                $paymentStatusColumn => 'refund_requested',
                'updated_at' => $now,
            ]);

            $this->insertOrderHistory($sourceConnection, [
                'order_id' => $id,
                'changed_by' => $this->nullableString($data['user_id'] ?? null),
                'changed_by_name' => 'Cliente',
                'change_type' => 'payment_change',
                'field_changed' => 'payment_status',
                'old_value' => $oldPaymentStatus,
                'new_value' => 'refund_requested',
                'description' => 'Solicitud de reembolso creada por el cliente.',
                'created_at' => $now,
            ]);
        }

        $this->insertOrderHistory($sourceConnection, [
            'order_id' => $id,
            'changed_by' => $this->nullableString($data['user_id'] ?? null),
            'changed_by_name' => 'Cliente',
            'change_type' => 'refund_request',
            'field_changed' => 'refund_status',
            'old_value' => null,
            'new_value' => 'requested',
            'description' => 'Solicitud de reembolso registrada. Motivo: ' . trim((string) $data['reason']),
            'created_at' => $now,
        ]);

        $hydratedOrder = $this->hydrateOrderCustomerIdentity($order, $sourceConnection);
        if ($paymentStatusColumn && !$this->sameNormalizedValue($oldPaymentStatus, 'refund_requested')) {
            $this->notifyOrderUpdateChannels($hydratedOrder, $sourceConnection, 'payment_status', $oldPaymentStatus, 'refund_requested');
        }

        $this->sendRefundTeamEmail($hydratedOrder, $sourceConnection, (string) ($hydratedOrder->order_number ?? ('#' . $id)), $data['reason']);

        return response()->json([
            'message' => 'Solicitud de reembolso enviada correctamente.',
            'data' => [
                'id' => $requestId,
                'status' => 'requested',
                'payment_status' => 'refund_requested',
            ],
        ], 201);
    }

    /**
     * Actualiza campos administrativos de una orden (datos de
     * contacto, dirección, método de pago, notas). Usa un mapeo
     * de campos para adaptarse al esquema de la BD destino.
     */
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

            return $this->attachRefundEligibilityToOrders($query->limit(50)->get(), $connection);
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

    /**
     * Busca una orden por ID en la conexión indicada y
     * etiqueta el origen (microservicio o legacy).
     */
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

    /**
     * Obtiene los ítems de una orden desde la conexión indicada.
     */
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

    /**
     * Enriquece los ítems de la orden con imágenes del catálogo
     * de productos. Usa el servicio de catálogo interno
     * (services/catalog) para resolver la URL de cada imagen.
     */
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

    /**
     * Resuelve la URL base del endpoint de productos del catálogo
     * para consultar imágenes. Utilizado por enrichOrderItemsWithCatalogImages.
     */
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

    /**
     * Selecciona la primera imagen de producto disponible
     * entre las candidatas (primary, image_path, image, image_url).
     */
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

    /**
     * Recupera el historial de cambios de estado de una orden
     * desde la tabla order_status_history.
     */
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

    /**
     * Completa los datos del cliente (nombre, email, teléfono)
     * en la orden consultando la tabla users o el auth-service.
     */
    /**
     * Adjunta a cada orden el estado de solicitud y disponibilidad de reembolso.
     */
    private function attachRefundEligibilityToOrders(Collection $orders, ?string $connection): Collection
    {
        return $orders->map(function ($order) use ($connection) {
            $eligibility = $this->resolveOrderRefundEligibility($order, $connection);
            $order->refund_available = (bool) ($eligibility['available'] ?? false);
            $order->refund_deadline_at = $eligibility['deadline_at'] ?? null;
            $order->refund_policy_days = $eligibility['refund_days'] ?? null;
            $order->refund_request_status = $eligibility['request_status'] ?? null;
            $order->refund_message = $eligibility['message'] ?? null;

            return $order;
        })->values();
    }

    /**
     * Calcula si una orden entregada conserva ventana válida de reembolso por sus productos.
     */
    private function resolveOrderRefundEligibility(object $order, ?string $connection): array
    {
        if (!$this->hasTable('order_refund_requests', $connection)) {
            return ['available' => false, 'message' => 'El flujo de reembolso aún no está disponible.'];
        }

        $openRequest = $this->existingOpenRefundRequest($connection, (int) ($order->id ?? 0));
        if ($openRequest !== null) {
            return [
                'available' => false,
                'request_status' => $openRequest->status ?? 'requested',
                'message' => 'Ya tienes una solicitud de reembolso en revisión.',
            ];
        }

        $normalizedStatus = Str::lower(trim((string) ($order->status ?? $order->order_status ?? '')));
        if (!in_array($normalizedStatus, ['delivered', 'completed'], true)) {
            return ['available' => false, 'message' => 'El reembolso se habilita cuando el pedido está completado.'];
        }

        $items = $this->fetchOrderItemsByConnection($connection, (int) ($order->id ?? 0));
        $policy = $this->resolveRefundPolicyForItems($items);
        if (!($policy['is_refundable'] ?? false)) {
            return ['available' => false, 'message' => 'Los productos de este pedido no tienen reembolso activo.'];
        }

        $baseDate = $this->resolveRefundBaseDate($order);
        $deadline = $baseDate->copy()->addDays((int) $policy['refund_days'])->endOfDay();
        if (now()->greaterThan($deadline)) {
            return [
                'available' => false,
                'refund_days' => (int) $policy['refund_days'],
                'deadline_at' => $deadline->toIso8601String(),
                'message' => 'El plazo de reembolso ya venció.',
            ];
        }

        return [
            'available' => true,
            'refund_days' => (int) $policy['refund_days'],
            'deadline_at' => $deadline->toIso8601String(),
            'message' => 'Reembolso disponible.',
        ];
    }

    /**
     * Usa la fecha operativa más cercana a la entrega para calcular la ventana de reembolso.
     */
    private function resolveRefundBaseDate(object $order): Carbon
    {
        foreach (['delivered_at', 'completed_at', 'updated_at', 'created_at'] as $field) {
            $value = $this->nullableString($order->{$field} ?? null);
            if ($value !== null) {
                return Carbon::parse($value);
            }
        }

        return now();
    }

    /**
     * Revisa productos del pedido en catálogo y usa la ventana más amplia configurada.
     */
    private function resolveRefundPolicyForItems(Collection $items): array
    {
        $productIds = $items
            ->map(static fn ($item): int => (int) ($item->product_id ?? 0))
            ->filter(static fn (int $productId): bool => $productId > 0)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return ['is_refundable' => false, 'refund_days' => null];
        }

        $endpoint = $this->resolveCatalogProductImageEndpoint();
        if ($endpoint === null) {
            return ['is_refundable' => false, 'refund_days' => null];
        }

        $maxDays = 0;
        foreach ($productIds as $productId) {
            try {
                $response = Http::acceptJson()->timeout(4)->get($endpoint . '/' . $productId);
                if (!$response->successful()) {
                    continue;
                }

                $product = $response->json('data');
                if (!is_array($product) || !filter_var($product['is_refundable'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $maxDays = max($maxDays, (int) ($product['refund_days'] ?? 0));
            } catch (Throwable) {
                continue;
            }
        }

        return ['is_refundable' => $maxDays > 0, 'refund_days' => $maxDays > 0 ? $maxDays : null];
    }

    /**
     * Busca una solicitud abierta para prevenir duplicados del cliente.
     */
    private function existingOpenRefundRequest(?string $connection, int $orderId): ?object
    {
        if ($orderId <= 0 || !$this->hasTable('order_refund_requests', $connection)) {
            return null;
        }

        return $this->query($connection)
            ->table('order_refund_requests')
            ->where('order_id', $orderId)
            ->whereIn('status', ['requested', 'reviewing', 'approved'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Guarda evidencia de reembolso en una ruta pública controlada por el servicio.
     */
    private function storeRefundEvidence(mixed $file): ?string
    {
        if (!$file instanceof \Illuminate\Http\UploadedFile) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = Str::uuid()->toString() . '.' . $extension;
        $destination = public_path('uploads/refunds');

        if (!is_dir($destination)) {
            mkdir($destination, 0775, true);
        }

        $file->move($destination, $filename);

        return '/uploads/refunds/' . $filename;
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

    /**
     * Busca un usuario por ID, primero en la tabla users de la
     * conexión dada y luego como fallback en auth-service.
     */
    private function findUserById(?string $connection, string $userId): ?object
    {
        $user = null;

        try {
            if ($this->hasTable('users', $connection)) {
                $user = $this->query($connection)
                    ->table('users')
                    ->select('id', 'name', 'email', 'phone')
                    ->where('id', $userId)
                    ->first();
            }
        } catch (Throwable) {
            $user = null;
        }

        if ($user) {
            return $user;
        }

        $authProfile = $this->findAuthUserById($userId);
        if ($authProfile === null) {
            return null;
        }

        return (object) $authProfile;
    }

    /**
     * Consulta el perfil de un usuario en auth-service
     * utilizando un endpoint interno con token opcional.
     * Los resultados se cachean localmente en $authProfilesById.
     */
    private function findAuthUserById(string $userId): ?array
    {
        $normalizedUserId = trim($userId);
        if ($normalizedUserId === '' || app()->environment('testing')) {
            return null;
        }

        if (array_key_exists($normalizedUserId, $this->authProfilesById)) {
            return $this->authProfilesById[$normalizedUserId];
        }

        $endpoint = $this->resolveAuthProfilesEndpoint();
        if ($endpoint === null) {
            $this->authProfilesById[$normalizedUserId] = null;
            return null;
        }

        try {
            $request = Http::acceptJson()->timeout(4);
            $token = trim((string) config('services.auth.internal_token', ''));

            if ($token !== '') {
                $request = $request->withHeaders(['X-Internal-Token' => $token]);
            }

            $response = $request->get($endpoint, [
                'ids' => $normalizedUserId,
            ]);

            if (!$response->successful()) {
                $this->authProfilesById[$normalizedUserId] = null;
                return null;
            }

            $profiles = $response->json('data');
            if (!is_array($profiles)) {
                $this->authProfilesById[$normalizedUserId] = null;
                return null;
            }

            foreach ($profiles as $profile) {
                if (!is_array($profile)) {
                    continue;
                }

                $profileId = trim((string) ($profile['id'] ?? ''));
                if ($profileId === '') {
                    continue;
                }

                $email = trim((string) ($profile['email'] ?? ''));

                $this->authProfilesById[$profileId] = [
                    'id' => $profileId,
                    'name' => trim((string) ($profile['name'] ?? '')),
                    'email' => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                    'phone' => trim((string) ($profile['phone'] ?? '')),
                ];
            }

            if (!array_key_exists($normalizedUserId, $this->authProfilesById)) {
                $this->authProfilesById[$normalizedUserId] = null;
            }

            return $this->authProfilesById[$normalizedUserId];
        } catch (Throwable $exception) {
            Log::warning('No se pudo resolver perfil de usuario desde auth-service.', [
                'user_id' => $normalizedUserId,
                'error' => $exception->getMessage(),
            ]);

            $this->authProfilesById[$normalizedUserId] = null;
            return null;
        }
    }

    /**
     * Resuelve la URL del endpoint de perfiles internos
     * del auth-service para obtener datos de usuario.
     */
    private function resolveAuthProfilesEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.auth.base_url', 'http://auth-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/internal/users/profiles';
        }

        return $baseUrl . '/api/internal/users/profiles';
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

    /**
     * Determina en qué conexión (microservicio o legacy) existe
     * la orden. Si se prefiere legacy, lo prueba primero.
     */
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

    /**
     * Normaliza el parámetro source a la constante de conexión
     * legacy o null para el microservicio por defecto.
     */
    private function normalizeSourceConnection(mixed $source): ?string
    {
        $value = strtolower(trim((string) ($source ?? '')));

        if (in_array($value, ['legacy', 'legacy_mysql'], true)) {
            return self::LEGACY_CONNECTION;
        }

        return null;
    }

    /**
     * Retorna el nombre de conexión o el default si es null.
     */
    private function resolveConnectionName(?string $connection): string
    {
        return $connection ?: config('database.default');
    }

    /**
     * Verifica si una tabla existe en la conexión indicada.
     */
    private function hasTable(string $table, ?string $connection): bool
    {
        return Schema::connection($this->resolveConnectionName($connection))->hasTable($table);
    }

    /**
     * Retorna la primera columna existente entre las candidatas
     * en la tabla y conexión dadas. Útil para lidiar con esquemas
     * legacy que usan nombres de columna distintos.
     */
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

    /**
     * Inserta un registro en order_status_history de forma segura,
     * tolerando que la tabla no exista (ej: entornos sin historial).
     */
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

    /**
     * Verifica si un cliente (por user_id o user_email) es el
     * propietario de la orden, para autorizar cancelación o descarga.
     */
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

    /**
     * Determina si el estado actual permite cancelación por parte
     * del cliente (pendiente, en proceso, confirmado o pagado).
     */
    private function isCustomerCancelableStatus(?string $status): bool
    {
        $normalized = Str::lower(trim((string) ($status ?? '')));

        return in_array($normalized, ['pending', 'processing', 'confirmed', 'paid'], true);
    }

    /**
     * Indica si la cancelación de una orden debe activar un
     * proceso de reembolso, basado en el estado de pago actual.
     */
    private function requiresRefundOnCancel(?string $paymentStatus, ?string $orderStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        if (in_array($normalizedPaymentStatus, ['paid', 'approved', 'verified'], true)) {
            return true;
        }

        $normalizedOrderStatus = Str::lower(trim((string) ($orderStatus ?? '')));
        return $normalizedPaymentStatus === '' && in_array($normalizedOrderStatus, ['paid', 'confirmed'], true);
    }

    /**
     * Evalúa si se debe mover el pago a pending_refund
     * cuando se cancela una orden que ya tenía un pago confirmado.
     */
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

    /**
     * Indica si la conexión soporta el flujo de reserva de
     * inventario (solo el microservicio, no legacy).
     */
    private function supportsReservationWorkflow(?string $sourceConnection): bool
    {
        return $sourceConnection !== self::LEGACY_CONNECTION;
    }

    /**
     * Determina si la orden debe pasar a revisión al recibir
     * un comprobante de pago, según los estados reviewable definidos.
     */
    private function shouldMoveOrderToReview(?string $status): bool
    {
        $normalizedStatus = Str::lower(trim((string) ($status ?? '')));
        return in_array($normalizedStatus, self::REVIEWABLE_ORDER_STATUSES, true);
    }

    /**
     * Determina si un estado de orden debe confirmar la reserva
     * de inventario (paid, confirmed, processing, etc.).
     */
    private function shouldConfirmReservationForOrderStatus(?string $status): bool
    {
        $normalizedStatus = Str::lower(trim((string) ($status ?? '')));
        return in_array($normalizedStatus, self::CONFIRM_RESERVATION_STATUS_VALUES, true);
    }

    /**
     * Determina si un estado de orden debe liberar la reserva
     * de inventario (cancelled, rejected, failed).
     */
    private function shouldReleaseReservationForOrderStatus(?string $status): bool
    {
        $normalizedStatus = Str::lower(trim((string) ($status ?? '')));
        return in_array($normalizedStatus, self::RELEASE_RESERVATION_STATUS_VALUES, true);
    }

    /**
     * Determina si un estado de pago debe confirmar la reserva
     * de inventario (paid, approved, verified).
     */
    private function shouldConfirmReservationForPaymentStatus(?string $paymentStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        return in_array($normalizedPaymentStatus, self::CONFIRM_RESERVATION_PAYMENT_VALUES, true);
    }

    /**
     * Determina si un estado de pago debe liberar la reserva
     * de inventario (rejected, failed, cancelled).
     */
    private function shouldReleaseReservationForPaymentStatus(?string $paymentStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        return in_array($normalizedPaymentStatus, self::RELEASE_RESERVATION_PAYMENT_VALUES, true);
    }

    /**
     * Determina si el pago rechazado/fallido debe provocar la
     * cancelación automática de la orden.
     */
    private function shouldAutoCancelOrderForPaymentStatus(?string $paymentStatus): bool
    {
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));
        return in_array($normalizedPaymentStatus, ['rejected', 'failed', 'cancelled', 'canceled'], true);
    }

    private function shouldMoveVerifiedPaymentToRefund(?string $orderStatus, ?string $paymentStatus): bool
    {
        // Reutiliza el flujo de reembolso ya visible en cuenta/admin cuando una orden cancelada recibe pago confirmado.
        $normalizedOrderStatus = Str::lower(trim((string) ($orderStatus ?? '')));
        $normalizedPaymentStatus = Str::lower(trim((string) ($paymentStatus ?? '')));

        return in_array($normalizedOrderStatus, ['cancelled', 'canceled'], true)
            && in_array($normalizedPaymentStatus, ['paid', 'approved', 'verified'], true);
    }

    /**
     * Genera una descripción por defecto para el historial cuando
     * cambia el estado de pago, explicando automatizaciones.
     */
    private function defaultPaymentStatusChangeDescription(?string $orderStatus, ?string $requestedPaymentStatus, string $targetPaymentStatus): string
    {
        // Explica la automatización para que el historial no parezca una edición manual contradictoria.
        if ($this->shouldMoveVerifiedPaymentToRefund($orderStatus, $requestedPaymentStatus) && $targetPaymentStatus === 'pending_refund') {
            return 'Pago verificado sobre orden cancelada; se inicia proceso de reembolso.';
        }

        return 'Cambio de estado de pago de la orden';
    }

    /**
     * Cancela la orden y marca el pago como fallido cuando
     * la reserva de inventario no pudo confirmarse por falta
     * de stock. Notifica al cliente automáticamente.
     */
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

    /**
     * Notifica la creación de una orden al cliente vía
     * notificación push y correo electrónico.
     */
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

    /**
     * Notifica la cancelación de una orden al cliente y,
     * si aplica, envía correo al equipo de reembolsos.
     */
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

    /**
     * Construye el mensaje de notificación para el cliente
     * según si requiere reembolso o no, incluyendo el motivo.
     */
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

    /**
     * Envía un correo al equipo financiero con los detalles de
     * la solicitud de reembolso cuando un cliente cancela un
     * pedido ya pagado.
     */
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

    /**
     * Genera el HTML del correo de solicitud de reembolso
     * dirigido al equipo financiero.
     */
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

    /**
     * Notifica cambios de estado a través de canales en tiempo
     * real (websocket), notificaciones push y correo electrónico.
     */
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

        $this->publishOrderStatusRealtimeUpdate($order, $sourceConnection, $field, $oldValue, $normalizedNewValue);

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

    /**
     * Publica el cambio de estado en el canal en tiempo real
     * reutilizando StockReservationRealtimePublisher para que
     * el gateway websocket entregue actualizaciones de pedido.
     */
    private function publishOrderStatusRealtimeUpdate(object $order, ?string $sourceConnection, string $field, ?string $oldValue, string $newValue): void
    {
        $orderId = (int) ($order->id ?? 0);
        if ($orderId <= 0) {
            return;
        }

        // Reutiliza el canal de reservas para que el gateway websocket entregue cambios de pedido y stock juntos.
        $payload = [
            'order_id' => $orderId,
            'order_number' => $this->nullableString($order->order_number ?? null),
            'user_id' => $this->nullableString($order->user_id ?? null),
            'user_email' => $this->nullableString($order->user_email ?? $order->customer_email ?? $order->billing_email ?? null),
            'source' => $sourceConnection === self::LEGACY_CONNECTION ? 'legacy' : 'orders',
            'field' => $field,
            'old_value' => $this->nullableString($oldValue),
            'new_value' => $newValue,
            'status' => $field === 'status' ? $newValue : $this->nullableString($order->status ?? $order->order_status ?? null),
            'payment_status' => $field === 'payment_status' ? $newValue : $this->nullableString($order->payment_status ?? null),
        ];

        $event = $field === 'payment_status' ? 'order.payment_status.updated' : 'order.status.updated';
        $this->stockRealtimePublisher->publish($event, $payload);
    }

    /**
     * Construye el mensaje legible para el cliente sobre la
     * actualización de estado o pago de su orden.
     */
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

    /**
     * Convierte un valor de estado interno a una etiqueta
     * legible en español para mostrar al cliente.
     */
    private function normalizeOperationalLabel(?string $value, string $field): ?string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return null;
        }

        $statusMap = [
            'pending' => 'Pendiente',
            'in_review' => 'En proceso',
            'en_revision' => 'En proceso',
            'processing' => 'En proceso',
            'in_process' => 'En proceso',
            'confirmed' => 'Confirmado',
            'paid' => 'Pagado',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'completed' => 'Completado',
            'expired' => 'Cancelado',
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
            'refund_requested' => 'Reembolso solicitado',
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

    /**
     * Envía una notificación push al usuario a través del
     * notification-service, registrando el evento en la BD
     * de notificaciones del sistema.
     */
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

    /**
     * Envía un correo de actualización de orden al cliente
     * usando una plantilla HTML predefinida.
     */
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

    /**
     * Genera el HTML del correo de actualización de orden
     * usando una plantilla responsive con branding de Angelow.
     */
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

    /**
     * Obtiene el correo del cliente asociado a la orden,
     * probando múltiples campos candidatos y con fallback
     * a la tabla users o auth-service. Reutiliza findUserById.
     */
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

    /**
     * Obtiene el nombre del cliente asociado a la orden,
     * probando campos candidatos y con fallback a users/auth-service.
     */
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

    /**
     * Resuelve la URL del endpoint de notificaciones
     * del notification-service para crear notificaciones push.
     */
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

    /**
     * Compara dos valores string normalizándolos (minúsculas, trim)
     * para evitar falsos negativos por diferencias de formato.
     */
    private function sameNormalizedValue(?string $left, ?string $right): bool
    {
        return Str::lower(trim((string) ($left ?? ''))) === Str::lower(trim((string) ($right ?? '')));
    }

    /**
     * Normaliza un valor mixto a string nulleable: si es vacío
     * retorna null, en caso contrario el string trim.
     */
    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
