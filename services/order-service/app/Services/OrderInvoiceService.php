<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class OrderInvoiceService
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    private const COMPLETED_ORDER_STATUSES = ['delivered', 'completed'];
    private const PAID_PAYMENT_STATUSES = ['paid', 'verified', 'approved'];

    public function sendCheckoutConfirmationEmail(int $orderId, array $context = [], ?string $preferredConnection = null): array
    {
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'No encontramos la orden para enviar el correo de confirmación.',
            ];
        }

        $order = $payload['order'];
        $items = $payload['items'];

        $customerEmail = $this->resolveContextCustomerEmail($context)
            ?? $this->resolveCustomerEmail($order, $payload['connection']);
        if ($customerEmail === null) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La orden no tiene un correo válido para el envío de confirmación.',
            ];
        }

        $customerName = $this->resolveCustomerName($order, $payload['connection']);
        $orderLabel = trim((string) ($order['order_number'] ?? ''));
        if ($orderLabel === '') {
            $orderLabel = '#' . (string) ($order['id'] ?? $orderId);
        }

        $subject = 'Confirmación de pedido ' . $orderLabel;
        $html = $this->buildCheckoutConfirmationHtml($order, $items, $customerName, $context);

        try {
            Mail::html($html, function ($mail) use ($customerEmail, $customerName, $subject): void {
                $mail->to($customerEmail, $customerName)->subject($subject);
            });

            return [
                'ok' => true,
                'message' => 'Correo de confirmación enviado.',
                'order_id' => $orderId,
            ];
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar correo de confirmación de checkout.', [
                'order_id' => $orderId,
                'email' => $customerEmail,
                'error' => $exception->getMessage(),
                'mailer' => (string) config('mail.default', 'log'),
            ]);

            return [
                'ok' => false,
                'code' => 500,
                'message' => 'No se pudo enviar el correo de confirmación.',
            ];
        }
    }

    public function ensureInvoiceForCompletedOrder(int $orderId, ?string $preferredConnection = null): array
    {
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'Orden no encontrada para generar factura.',
            ];
        }

        $order = $payload['order'];

        if (!$this->isReadyForInvoice($order)) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'La orden aún no cumple condiciones de entrega y pago para facturar.',
            ];
        }

        if ($this->orderHasInvoice($order)) {
            return [
                'ok' => true,
                'already_generated' => true,
                'message' => 'La factura ya estaba generada previamente.',
                'invoice_number' => (string) ($order['invoice_number'] ?? ''),
            ];
        }

        return $this->generateAndSendInvoice($payload, false);
    }

    public function resendInvoiceEmail(int $orderId, ?string $preferredConnection = null): array
    {
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'Orden no encontrada para reenviar factura.',
            ];
        }

        $order = $payload['order'];

        if (!$this->isReadyForInvoice($order)) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La factura solo se envía cuando la orden está entregada y el pago verificado.',
            ];
        }

        return $this->generateAndSendInvoice($payload, true);
    }

    public function buildInvoicePdfForDownload(int $orderId, ?string $preferredConnection = null): array
    {
        $payload = $this->buildOrderPayload($orderId, $preferredConnection);

        if (!$payload) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'No encontramos la orden solicitada.',
            ];
        }

        $order = $payload['order'];
        if (!$this->isReadyForInvoice($order) && !$this->orderHasInvoice($order)) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La orden todavía no tiene una factura disponible.',
            ];
        }

        try {
            $invoiceNumber = $this->resolveInvoiceNumber($order);
            $pdfContent = $this->generateInvoicePdfContent($order, $payload['items'], $invoiceNumber);

            return [
                'ok' => true,
                'content' => $pdfContent,
                'filename' => 'factura_' . $this->sanitizeFileSegment($invoiceNumber) . '.pdf',
            ];
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar PDF de factura para descarga.', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'code' => 500,
                'message' => 'No se pudo generar el PDF de la factura.',
            ];
        }
    }

    public function listGeneratedInvoices(array $filters = [], int $limit = 200): Collection
    {
        $safeLimit = max(1, min($limit, 500));
        $safeFilters = $this->normalizeInvoiceFilters($filters);

        $distributedRows = $this->fetchInvoicesByConnection(null, $safeFilters, $safeLimit * 2);
        $legacyRows = $this->fetchInvoicesByConnection(self::LEGACY_CONNECTION, $safeFilters, $safeLimit * 2);

        return $distributedRows
            ->concat($legacyRows)
            ->unique(function (array $row): string {
                $invoiceNumber = strtolower(trim((string) ($row['invoice_number'] ?? '')));
                if ($invoiceNumber !== '') {
                    return 'invoice:' . $invoiceNumber;
                }

                $orderNumber = strtolower(trim((string) ($row['order_number'] ?? '')));
                if ($orderNumber !== '') {
                    return 'order:' . $orderNumber;
                }

                return 'source:' . strtolower((string) ($row['order_source'] ?? 'microservice')) . ':id:' . (string) ($row['id'] ?? '0');
            })
            ->sortByDesc(function (array $row): int {
                $rawDate = (string) ($row['invoice_date'] ?? $row['created_at'] ?? '');
                $timestamp = strtotime($rawDate);
                return $timestamp ?: 0;
            })
            ->values()
            ->take($safeLimit)
            ->values();
    }

    private function generateAndSendInvoice(array $payload, bool $forceSend): array
    {
        $order = $payload['order'];
        $items = $payload['items'];
        $connection = $payload['connection'];
        $orderId = (int) ($order['id'] ?? 0);

        $customerEmail = $this->resolveCustomerEmail($order, $connection);
        if ($customerEmail === null) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'La orden no tiene correo válido para enviar la factura.',
            ];
        }

        $customerName = $this->resolveCustomerName($order, $connection);
        $invoiceNumber = $this->resolveInvoiceNumber($order);

        try {
            $pdfContent = $this->generateInvoicePdfContent($order, $items, $invoiceNumber);
            $emailHtml = $this->buildInvoiceEmailHtml($order, $customerName, $invoiceNumber);

            Mail::html($emailHtml, function ($mail) use ($customerEmail, $customerName, $invoiceNumber, $pdfContent): void {
                $mail
                    ->to($customerEmail, $customerName)
                    ->subject('Factura electrónica ' . $invoiceNumber)
                    ->attachData(
                        $pdfContent,
                        'factura_' . $this->sanitizeFileSegment($invoiceNumber) . '.pdf',
                        ['mime' => 'application/pdf']
                    );
            });

            $this->persistInvoiceMetadata($connection, $orderId, $invoiceNumber);

            return [
                'ok' => true,
                'message' => $forceSend ? 'Factura reenviada correctamente.' : 'Factura generada y enviada correctamente.',
                'invoice_number' => $invoiceNumber,
                'order_id' => $orderId,
            ];
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar/enviar la factura de la orden.', [
                'order_id' => $orderId,
                'invoice_number' => $invoiceNumber,
                'error' => $exception->getMessage(),
                'mailer' => (string) config('mail.default', 'log'),
            ]);

            return [
                'ok' => false,
                'code' => 500,
                'message' => 'No fue posible enviar la factura al cliente.',
            ];
        }
    }

    private function buildOrderPayload(int $orderId, ?string $preferredConnection = null): ?array
    {
        ['order' => $order, 'connection' => $connection] = $this->resolveOrderSource($orderId, $preferredConnection);

        if (!$order) {
            return null;
        }

        $normalizedOrder = $this->normalizeOrder((array) $order, $connection);
        $items = $this->fetchOrderItemsByConnection($connection, $orderId)
            ->map(static function ($item) {
                return [
                    'id' => (int) ($item->id ?? 0),
                    'product_name' => trim((string) ($item->product_name ?? 'Producto')), 
                    'variant_name' => trim((string) ($item->variant_name ?? '')),
                    'price' => (float) ($item->price ?? 0),
                    'quantity' => (int) ($item->quantity ?? 0),
                    'total' => (float) ($item->total ?? 0),
                ];
            })
            ->values()
            ->all();

        return [
            'order' => $normalizedOrder,
            'items' => $items,
            'connection' => $connection,
        ];
    }

    private function fetchInvoicesByConnection(?string $connection, array $filters, int $limit): Collection
    {
        if (!$this->hasTable('orders', $connection)) {
            return collect();
        }

        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';
        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $connection);
        $customerNameColumn = $this->firstExistingColumn('orders', ['user_name', 'customer_name', 'billing_name'], $connection);
        $customerEmailColumn = $this->firstExistingColumn('orders', ['user_email', 'customer_email', 'billing_email'], $connection);
        $customerPhoneColumn = $this->firstExistingColumn('orders', ['user_phone', 'customer_phone', 'billing_phone', 'phone'], $connection);
        $invoiceNumberColumn = $this->firstExistingColumn('orders', ['invoice_number'], $connection);
        $invoiceDateColumn = $this->firstExistingColumn('orders', ['invoice_date'], $connection);
        $likeOperator = $this->likeOperator($connection);

        if (!$invoiceNumberColumn && !$invoiceDateColumn) {
            return collect();
        }

        try {
            $query = $this->query($connection)->table('orders')->select('orders.*');

            if ($statusColumn !== 'status') {
                $query->addSelect("orders.{$statusColumn} as status");
            }

            if ($paymentStatusColumn && $paymentStatusColumn !== 'payment_status') {
                $query->addSelect("orders.{$paymentStatusColumn} as payment_status");
            }

            if ($customerNameColumn && $customerNameColumn !== 'user_name') {
                $query->addSelect("orders.{$customerNameColumn} as user_name");
            }

            if ($customerEmailColumn && $customerEmailColumn !== 'user_email') {
                $query->addSelect("orders.{$customerEmailColumn} as user_email");
            }

            if ($customerPhoneColumn && $customerPhoneColumn !== 'user_phone') {
                $query->addSelect("orders.{$customerPhoneColumn} as user_phone");
            }

            if ($invoiceNumberColumn && $invoiceNumberColumn !== 'invoice_number') {
                $query->addSelect("orders.{$invoiceNumberColumn} as invoice_number");
            }

            if ($invoiceDateColumn && $invoiceDateColumn !== 'invoice_date') {
                $query->addSelect("orders.{$invoiceDateColumn} as invoice_date");
            }

            $query->where(function ($invoiceQuery) use ($invoiceNumberColumn, $invoiceDateColumn): void {
                if ($invoiceNumberColumn) {
                    $invoiceQuery
                        ->whereNotNull("orders.{$invoiceNumberColumn}")
                        ->where("orders.{$invoiceNumberColumn}", '!=', '');
                }

                if ($invoiceDateColumn) {
                    $invoiceQuery->orWhereNotNull("orders.{$invoiceDateColumn}");
                }
            });

            if ($filters['search'] !== '') {
                $term = $filters['search'];
                $query->where(function ($searchQuery) use ($term, $customerNameColumn, $customerEmailColumn, $invoiceNumberColumn, $likeOperator): void {
                    $searchQuery->where('orders.order_number', $likeOperator, "%{$term}%");

                    if ($invoiceNumberColumn) {
                        $searchQuery->orWhere("orders.{$invoiceNumberColumn}", $likeOperator, "%{$term}%");
                    }

                    if ($customerNameColumn) {
                        $searchQuery->orWhere("orders.{$customerNameColumn}", $likeOperator, "%{$term}%");
                    }

                    if ($customerEmailColumn) {
                        $searchQuery->orWhere("orders.{$customerEmailColumn}", $likeOperator, "%{$term}%");
                    }
                });
            }

            if ($filters['status'] !== '') {
                $query->where("orders.{$statusColumn}", $filters['status']);
            }

            if ($filters['payment_status'] !== '' && $paymentStatusColumn) {
                $query->where("orders.{$paymentStatusColumn}", $filters['payment_status']);
            }

            if ($filters['from_date'] !== '') {
                $column = $invoiceDateColumn ? "orders.{$invoiceDateColumn}" : 'orders.created_at';
                $query->where($column, '>=', $filters['from_date'] . ' 00:00:00');
            }

            if ($filters['to_date'] !== '') {
                $column = $invoiceDateColumn ? "orders.{$invoiceDateColumn}" : 'orders.created_at';
                $query->where($column, '<=', $filters['to_date'] . ' 23:59:59');
            }

            if ($filters['source'] !== '') {
                $expectedSource = $filters['source'];
                $currentSource = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';
                if ($expectedSource !== $currentSource) {
                    return collect();
                }
            }

            if ($invoiceDateColumn) {
                $query->orderByDesc("orders.{$invoiceDateColumn}");
            }

            $rows = $query
                ->orderByDesc('orders.created_at')
                ->limit($limit)
                ->get()
                ->map(function ($row) use ($connection) {
                    $normalized = $this->normalizeOrder((array) $row, $connection);
                    $normalized['order_source'] = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';
                    return $normalized;
                })
                ->values();

            return $this->hydrateRowsWithCustomerData($rows, $connection);
        } catch (Throwable) {
            return collect();
        }
    }

    private function hydrateRowsWithCustomerData(Collection $rows, ?string $connection): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $userIds = $rows
            ->map(static fn (array $row): string => trim((string) ($row['user_id'] ?? '')))
            ->filter(static fn (string $userId): bool => $userId !== '')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return $rows;
        }

        $usersById = $this->loadUsersByIds($connection, $userIds->all());

        if ($connection !== self::LEGACY_CONNECTION) {
            $missingIds = $userIds
                ->reject(static fn (string $userId): bool => $usersById->has($userId))
                ->values();

            if ($missingIds->isNotEmpty()) {
                $legacyUsers = $this->loadUsersByIds(self::LEGACY_CONNECTION, $missingIds->all());
                $usersById = $usersById->merge($legacyUsers);
            }
        }

        return $rows
            ->map(static function (array $row) use ($usersById): array {
                $userId = trim((string) ($row['user_id'] ?? ''));
                if ($userId === '' || !$usersById->has($userId)) {
                    return $row;
                }

                $user = $usersById->get($userId);

                if (trim((string) ($row['customer_name'] ?? '')) === '') {
                    $row['customer_name'] = trim((string) ($user->name ?? ''));
                }

                if (trim((string) ($row['customer_email'] ?? '')) === '') {
                    $row['customer_email'] = trim((string) ($user->email ?? ''));
                }

                if (trim((string) ($row['customer_phone'] ?? '')) === '') {
                    $row['customer_phone'] = trim((string) ($user->phone ?? ''));
                }

                return $row;
            })
            ->values();
    }

    private function loadUsersByIds(?string $connection, array $userIds): Collection
    {
        if (empty($userIds) || !$this->hasTable('users', $connection)) {
            return collect();
        }

        try {
            return $this->query($connection)
                ->table('users')
                ->select('id', 'name', 'email', 'phone')
                ->whereIn('id', $userIds)
                ->get()
                ->keyBy(static fn ($user): string => (string) $user->id);
        } catch (Throwable) {
            return collect();
        }
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

    private function findOrderByConnection(?string $connection, int $orderId): ?object
    {
        if (!$this->hasTable('orders', $connection)) {
            return null;
        }

        try {
            return $this->query($connection)
                ->table('orders')
                ->where('id', $orderId)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function fetchOrderItemsByConnection(?string $connection, int $orderId): Collection
    {
        if (!$this->hasTable('order_items', $connection)) {
            return collect();
        }

        try {
            return $this->query($connection)
                ->table('order_items')
                ->where('order_id', $orderId)
                ->orderBy('id')
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function normalizeOrder(array $row, ?string $connection): array
    {
        $status = (string) ($row['status'] ?? $row['order_status'] ?? 'pending');
        $paymentStatus = (string) ($row['payment_status'] ?? 'pending');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'order_number' => trim((string) ($row['order_number'] ?? '')),
            'invoice_number' => trim((string) ($row['invoice_number'] ?? '')),
            'user_id' => trim((string) ($row['user_id'] ?? '')),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => trim((string) ($row['payment_method'] ?? '')),
            'subtotal' => (float) ($row['subtotal'] ?? 0),
            'shipping_cost' => (float) ($row['shipping_cost'] ?? 0),
            'discount_amount' => (float) ($row['discount_amount'] ?? 0),
            'total' => (float) ($row['total'] ?? 0),
            'shipping_address' => trim((string) ($row['shipping_address'] ?? '')),
            'shipping_city' => trim((string) ($row['shipping_city'] ?? '')),
            'billing_address' => trim((string) ($row['billing_address'] ?? '')),
            'notes' => trim((string) ($row['notes'] ?? '')),
            'created_at' => $row['created_at'] ?? null,
            'invoice_date' => $row['invoice_date'] ?? null,
            'customer_name' => trim((string) ($row['user_name'] ?? $row['customer_name'] ?? $row['billing_name'] ?? '')),
            'customer_email' => trim((string) ($row['user_email'] ?? $row['customer_email'] ?? $row['billing_email'] ?? '')),
            'customer_phone' => trim((string) ($row['user_phone'] ?? $row['customer_phone'] ?? $row['billing_phone'] ?? $row['phone'] ?? '')),
            'order_source' => $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice',
        ];
    }

    private function resolveCustomerEmail(array $order, ?string $connection): ?string
    {
        $candidates = [
            trim((string) ($order['customer_email'] ?? '')),
            trim((string) ($order['billing_email'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        $user = $this->findUserById($connection, trim((string) ($order['user_id'] ?? '')));
        if (!$user && $connection !== self::LEGACY_CONNECTION) {
            $user = $this->findUserById(self::LEGACY_CONNECTION, trim((string) ($order['user_id'] ?? '')));
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function resolveContextCustomerEmail(array $context): ?string
    {
        $email = trim((string) ($context['customer_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function resolveCustomerName(array $order, ?string $connection): string
    {
        $name = trim((string) ($order['customer_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $user = $this->findUserById($connection, trim((string) ($order['user_id'] ?? '')));
        if (!$user && $connection !== self::LEGACY_CONNECTION) {
            $user = $this->findUserById(self::LEGACY_CONNECTION, trim((string) ($order['user_id'] ?? '')));
        }

        $resolvedName = trim((string) ($user->name ?? ''));
        if ($resolvedName !== '') {
            return $resolvedName;
        }

        return 'Cliente';
    }

    private function findUserById(?string $connection, string $userId): ?object
    {
        if ($userId === '' || !$this->hasTable('users', $connection)) {
            return null;
        }

        try {
            return $this->query($connection)
                ->table('users')
                ->select('id', 'name', 'email', 'phone')
                ->where('id', $userId)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function isReadyForInvoice(array $order): bool
    {
        $status = Str::lower(trim((string) ($order['status'] ?? 'pending')));
        $paymentStatus = Str::lower(trim((string) ($order['payment_status'] ?? 'pending')));

        return in_array($status, self::COMPLETED_ORDER_STATUSES, true)
            && in_array($paymentStatus, self::PAID_PAYMENT_STATUSES, true);
    }

    private function orderHasInvoice(array $order): bool
    {
        $invoiceNumber = trim((string) ($order['invoice_number'] ?? ''));
        $invoiceDate = trim((string) ($order['invoice_date'] ?? ''));

        return $invoiceNumber !== '' || $invoiceDate !== '';
    }

    private function resolveInvoiceNumber(array $order): string
    {
        $currentInvoice = trim((string) ($order['invoice_number'] ?? ''));
        if ($currentInvoice !== '') {
            return $currentInvoice;
        }

        $orderLabel = trim((string) ($order['order_number'] ?? ''));
        if ($orderLabel === '') {
            $orderLabel = (string) ($order['id'] ?? '0');
        }

        $suffix = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', $orderLabel) ?: (string) ($order['id'] ?? '0'));

        return 'FAC-' . $suffix;
    }

    private function persistInvoiceMetadata(?string $connection, int $orderId, string $invoiceNumber): void
    {
        if ($orderId <= 0 || !$this->hasTable('orders', $connection)) {
            return;
        }

        $updates = [];

        $updatedAtColumn = $this->firstExistingColumn('orders', ['updated_at'], $connection);
        if ($updatedAtColumn) {
            $updates[$updatedAtColumn] = now();
        }

        $invoiceNumberColumn = $this->firstExistingColumn('orders', ['invoice_number'], $connection);
        if ($invoiceNumberColumn) {
            $updates[$invoiceNumberColumn] = $invoiceNumber;
        }

        $invoiceDateColumn = $this->firstExistingColumn('orders', ['invoice_date'], $connection);
        if ($invoiceDateColumn) {
            $updates[$invoiceDateColumn] = now();
        }

        if ($updates === []) {
            return;
        }

        try {
            $this->query($connection)
                ->table('orders')
                ->where('id', $orderId)
                ->update($updates);
        } catch (Throwable $exception) {
            Log::warning('No se pudieron persistir metadatos de factura.', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function buildCheckoutConfirmationHtml(array $order, array $items, string $customerName, array $context): string
    {
        $safeName = e($customerName);
        $safeOrder = e($order['order_number'] ?: ('#' . (string) $order['id']));
        $safePaymentReference = e(trim((string) ($context['payment_reference'] ?? 'Sin referencia')));
        $safeBankName = e(trim((string) ($context['bank_name'] ?? 'Sin banco seleccionado')));
        $safeProofName = e(trim((string) ($context['payment_proof_name'] ?? 'Sin archivo reportado')));
        $safeOrderDate = e($this->formatBogotaDate($order['created_at'] ?? null));
        $safeStoreUrl = e($this->resolveStoreUrl() . '/mi-cuenta/pedidos');
        $safeShippingAddress = e($order['shipping_address'] !== '' ? $order['shipping_address'] : 'Dirección no disponible');
        $safeShippingCity = e($order['shipping_city'] !== '' ? $order['shipping_city'] : 'Ciudad no disponible');

        $summaryRows = $this->buildSummaryRows($order);
        $itemRows = $this->buildEmailItemRows($items);

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de pedido</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px 0; background: #f5f7fb; color: #1f2937; }
        .container { max-width: 720px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f7abf; color: #ffffff; padding: 22px 24px; }
        .header h1 { margin: 0 0 6px; font-size: 24px; }
        .content { padding: 24px; }
        .chip { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #e7f3fb; color: #0f7abf; font-weight: 700; margin: 12px 0; }
        .section-title { font-size: 17px; font-weight: 700; color: #0f7abf; margin: 24px 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fafc; color: #0f172a; padding: 10px; border-bottom: 1px solid #e5edf6; }
        td { padding: 10px; border-bottom: 1px solid #eef2f7; font-size: 14px; }
        .summary { background: #f8fafc; border: 1px solid #e5edf6; border-radius: 10px; padding: 14px; }
        .summary-row { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 6px; font-size: 14px; }
        .summary-row strong { color: #0f172a; }
        .summary-total { margin-top: 8px; padding-top: 8px; border-top: 1px solid #dbe5f0; font-size: 16px; color: #0f7abf; font-weight: 700; }
        .payment-box, .shipping-box { border: 1px solid #dbe5f0; border-radius: 10px; padding: 14px; background: #ffffff; }
        .payment-box p, .shipping-box p { margin: 0 0 8px; font-size: 14px; }
        .cta { margin-top: 18px; }
        .cta a { display: inline-block; background: #0f7abf; color: #ffffff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; }
        .footer { padding: 14px 24px; border-top: 1px solid #e5edf6; font-size: 12px; color: #64748b; background: #f8fbff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Pedido confirmado!</h1>
            <p style="margin:0; opacity:.92;">Recibimos tu compra y la estamos preparando.</p>
        </div>
        <div class="content">
            <p>Hola <strong>' . $safeName . '</strong>,</p>
            <p>Tu pedido ya quedó registrado en Angelow.</p>
            <span class="chip">Pedido ' . $safeOrder . ' · ' . $safeOrderDate . '</span>

            <div class="section-title">Resumen del pedido</div>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align:center;">Cant.</th>
                        <th style="text-align:right;">Precio</th>
                        <th style="text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>' . $itemRows . '</tbody>
            </table>

            <div class="section-title">Totales</div>
            <div class="summary">' . $summaryRows . '</div>

            <div class="section-title">Datos de pago reportados</div>
            <div class="payment-box">
                <p><strong>Banco:</strong> ' . $safeBankName . '</p>
                <p><strong>Referencia:</strong> ' . $safePaymentReference . '</p>
                <p><strong>Comprobante:</strong> ' . $safeProofName . '</p>
                <p><strong>Estado inicial:</strong> Pendiente de verificación</p>
            </div>

            <div class="section-title">Dirección de entrega</div>
            <div class="shipping-box">
                <p><strong>Dirección:</strong> ' . $safeShippingAddress . '</p>
                <p><strong>Ciudad/Zona:</strong> ' . $safeShippingCity . '</p>
            </div>

            <div class="cta">
                <a href="' . $safeStoreUrl . '">Ver mis pedidos</a>
            </div>
        </div>
        <div class="footer">
            Este correo fue generado automáticamente por Angelow. Te avisaremos cuando el pago sea verificado.
        </div>
    </div>
</body>
</html>';
    }

    private function buildInvoiceEmailHtml(array $order, string $customerName, string $invoiceNumber): string
    {
        $safeName = e($customerName);
        $safeInvoiceNumber = e($invoiceNumber);
        $safeOrderLabel = e($order['order_number'] !== '' ? $order['order_number'] : ('#' . (string) $order['id']));
        $safeIssueDate = e($this->formatBogotaDate(now()));
        $safeStoreUrl = e($this->resolveStoreUrl() . '/mi-cuenta/pedidos');

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Angelow</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px 0; background: #f5f7fb; color: #1f2937; }
        .container { max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f7abf; color: #ffffff; padding: 22px 24px; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 24px; }
        .chip { display: inline-block; margin: 10px 0 16px; padding: 6px 12px; border-radius: 999px; background: #e7f3fb; color: #0f7abf; font-weight: 700; }
        .cta a { display: inline-block; background: #0f7abf; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 700; }
        .footer { padding: 14px 24px; border-top: 1px solid #e5edf6; font-size: 12px; color: #64748b; background: #f8fbff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tu factura electrónica ya está lista</h1>
        </div>
        <div class="content">
            <p>Hola <strong>' . $safeName . '</strong>,</p>
            <p>Adjuntamos la factura de tu compra en Angelow.</p>
            <span class="chip">Factura ' . $safeInvoiceNumber . ' · Pedido ' . $safeOrderLabel . '</span>
            <p><strong>Fecha de emisión:</strong> ' . $safeIssueDate . ' (hora Colombia)</p>
            <p>Conserva este correo y el PDF adjunto para tus registros.</p>
            <p class="cta"><a href="' . $safeStoreUrl . '">Ir a mis pedidos</a></p>
        </div>
        <div class="footer">
            Si tienes dudas, responde por nuestros canales de soporte desde tu cuenta Angelow.
        </div>
    </div>
</body>
</html>';
    }

    private function buildEmailItemRows(array $items): string
    {
        if (empty($items)) {
            return '<tr><td colspan="4" style="text-align:center; color:#64748b;">Sin productos disponibles en esta orden.</td></tr>';
        }

        $rows = '';

        foreach ($items as $item) {
            $productName = e((string) ($item['product_name'] ?? 'Producto'));
            $variant = trim((string) ($item['variant_name'] ?? ''));
            $variantHtml = $variant !== '' ? '<br><small style="color:#64748b;">' . e($variant) . '</small>' : '';
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = '$ ' . number_format((float) ($item['price'] ?? 0), 0, ',', '.');
            $total = '$ ' . number_format((float) ($item['total'] ?? 0), 0, ',', '.');

            $rows .= '<tr>'
                . '<td><strong>' . $productName . '</strong>' . $variantHtml . '</td>'
                . '<td style="text-align:center;">' . $quantity . '</td>'
                . '<td style="text-align:right;">' . $price . '</td>'
                . '<td style="text-align:right;"><strong>' . $total . '</strong></td>'
                . '</tr>';
        }

        return $rows;
    }

    private function buildSummaryRows(array $order): string
    {
        $subtotal = (float) ($order['subtotal'] ?? 0);
        $shipping = (float) ($order['shipping_cost'] ?? 0);
        $discount = (float) ($order['discount_amount'] ?? 0);
        $total = (float) ($order['total'] ?? ($subtotal + $shipping - $discount));

        $rows = '<div class="summary-row"><span>Subtotal</span><strong>$ ' . number_format($subtotal, 0, ',', '.') . '</strong></div>';

        if ($discount > 0) {
            $rows .= '<div class="summary-row"><span>Descuento</span><strong>-$ ' . number_format($discount, 0, ',', '.') . '</strong></div>';
        }

        $rows .= '<div class="summary-row"><span>Envío</span><strong>$ ' . number_format($shipping, 0, ',', '.') . '</strong></div>';
        $rows .= '<div class="summary-row summary-total"><span>Total</span><strong>$ ' . number_format($total, 0, ',', '.') . '</strong></div>';

        return $rows;
    }

    private function generateInvoicePdfContent(array $order, array $items, string $invoiceNumber): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);

        $safeInvoiceNumber = e($invoiceNumber);
        $safeOrderLabel = e($order['order_number'] !== '' ? $order['order_number'] : ('#' . (string) $order['id']));
        $safeIssueDate = e($this->formatBogotaDate(now()));
        $safeOrderDate = e($this->formatBogotaDate($order['created_at'] ?? null));
        $safeCustomerName = e($this->resolveCustomerName($order, $order['order_source'] === 'legacy' ? self::LEGACY_CONNECTION : null));
        $safeCustomerEmail = e($this->resolveCustomerEmail($order, $order['order_source'] === 'legacy' ? self::LEGACY_CONNECTION : null) ?? 'No disponible');
        $safeCustomerPhone = e(trim((string) ($order['customer_phone'] ?? '')) ?: 'No disponible');
        $safeShippingAddress = e($order['shipping_address'] !== '' ? $order['shipping_address'] : 'Dirección no disponible');
        $safeShippingCity = e($order['shipping_city'] !== '' ? $order['shipping_city'] : 'Ciudad no disponible');
        $safePaymentMethod = e($this->translatePaymentMethod($order['payment_method'] ?? null));
        $safePaymentStatus = e($this->translateOperationalValue($order['payment_status'] ?? null, 'payment_status'));
        $safeOrderStatus = e($this->translateOperationalValue($order['status'] ?? null, 'status'));

        $itemRows = $this->buildPdfItemRows($items);
        $summaryRows = $this->buildPdfSummaryRows($order);

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 0; }
        .container { padding: 20px; }
        .header { border-bottom: 2px solid #0f7abf; padding-bottom: 12px; margin-bottom: 18px; }
        .header h1 { margin: 0; color: #0f7abf; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #4b5563; }
        .meta { margin-top: 10px; font-size: 11px; color: #334155; }
        .section-title { margin: 18px 0 8px; font-size: 14px; color: #0f7abf; font-weight: bold; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
        .line { margin-bottom: 5px; }
        .label { font-weight: bold; color: #0f172a; display: inline-block; min-width: 130px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f1f5f9; color: #0f172a; text-align: left; padding: 8px; border: 1px solid #e2e8f0; }
        td { padding: 8px; border: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals { margin-top: 10px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
        .totals-row { display: table; width: 100%; margin-bottom: 4px; }
        .totals-row span { display: table-cell; }
        .totals-row .value { text-align: right; font-weight: bold; }
        .totals-row.total { border-top: 1px solid #cbd5e1; padding-top: 6px; margin-top: 6px; font-size: 15px; color: #0f7abf; }
        .footer { margin-top: 16px; font-size: 10px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Factura electrónica Angelow</h1>
            <p>Documento generado automáticamente por finalización de pedido.</p>
            <div class="meta">
                <strong>Factura:</strong> ' . $safeInvoiceNumber . ' ·
                <strong>Pedido:</strong> ' . $safeOrderLabel . ' ·
                <strong>Emisión:</strong> ' . $safeIssueDate . '
            </div>
        </div>

        <div class="section-title">Datos del cliente</div>
        <div class="card">
            <div class="line"><span class="label">Nombre:</span> ' . $safeCustomerName . '</div>
            <div class="line"><span class="label">Correo:</span> ' . $safeCustomerEmail . '</div>
            <div class="line"><span class="label">Teléfono:</span> ' . $safeCustomerPhone . '</div>
        </div>

        <div class="section-title">Datos de la orden</div>
        <div class="card">
            <div class="line"><span class="label">Fecha de orden:</span> ' . $safeOrderDate . '</div>
            <div class="line"><span class="label">Estado orden:</span> ' . $safeOrderStatus . '</div>
            <div class="line"><span class="label">Estado pago:</span> ' . $safePaymentStatus . '</div>
            <div class="line"><span class="label">Método de pago:</span> ' . $safePaymentMethod . '</div>
            <div class="line"><span class="label">Dirección:</span> ' . $safeShippingAddress . '</div>
            <div class="line"><span class="label">Ciudad/Zona:</span> ' . $safeShippingCity . '</div>
        </div>

        <div class="section-title">Productos</div>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="center">Cant.</th>
                    <th class="right">Precio</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>' . $itemRows . '</tbody>
        </table>

        <div class="totals">' . $summaryRows . '</div>

        <div class="footer">
            Factura generada el ' . $safeIssueDate . ' (hora Colombia) · Angelow
        </div>
    </div>
</body>
</html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildPdfItemRows(array $items): string
    {
        if (empty($items)) {
            return '<tr><td colspan="4" class="center">Sin productos registrados.</td></tr>';
        }

        $rows = '';

        foreach ($items as $item) {
            $productName = e((string) ($item['product_name'] ?? 'Producto'));
            $variant = trim((string) ($item['variant_name'] ?? ''));
            $variantHtml = $variant !== '' ? '<br><small style="color:#64748b;">' . e($variant) . '</small>' : '';

            $rows .= '<tr>'
                . '<td>' . $productName . $variantHtml . '</td>'
                . '<td class="center">' . (int) ($item['quantity'] ?? 0) . '</td>'
                . '<td class="right">$ ' . number_format((float) ($item['price'] ?? 0), 0, ',', '.') . '</td>'
                . '<td class="right">$ ' . number_format((float) ($item['total'] ?? 0), 0, ',', '.') . '</td>'
                . '</tr>';
        }

        return $rows;
    }

    private function buildPdfSummaryRows(array $order): string
    {
        $subtotal = (float) ($order['subtotal'] ?? 0);
        $shipping = (float) ($order['shipping_cost'] ?? 0);
        $discount = (float) ($order['discount_amount'] ?? 0);
        $total = (float) ($order['total'] ?? ($subtotal + $shipping - $discount));

        $rows = '<div class="totals-row"><span>Subtotal</span><span class="value">$ ' . number_format($subtotal, 0, ',', '.') . '</span></div>';

        if ($discount > 0) {
            $rows .= '<div class="totals-row"><span>Descuento</span><span class="value">-$ ' . number_format($discount, 0, ',', '.') . '</span></div>';
        }

        $rows .= '<div class="totals-row"><span>Envío</span><span class="value">$ ' . number_format($shipping, 0, ',', '.') . '</span></div>';
        $rows .= '<div class="totals-row total"><span>Total factura</span><span class="value">$ ' . number_format($total, 0, ',', '.') . '</span></div>';

        return $rows;
    }

    private function translatePaymentMethod(?string $value): string
    {
        $map = [
            'transfer' => 'Transferencia',
            'bank_transfer' => 'Transferencia bancaria',
            'transferencia' => 'Transferencia',
            'transferencia_bancaria' => 'Transferencia bancaria',
            'cash' => 'Efectivo',
            'credit_card' => 'Tarjeta de crédito',
            'debit_card' => 'Tarjeta de débito',
            'pse' => 'PSE',
            'nequi' => 'Nequi',
        ];

        $normalized = Str::lower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return 'Transferencia';
        }

        if (array_key_exists($normalized, $map)) {
            return $map[$normalized];
        }

        return Str::title(str_replace(['_', '-'], ' ', $normalized));
    }

    private function translateOperationalValue(?string $value, string $context): string
    {
        $normalized = Str::lower(trim((string) ($value ?? '')));
        if ($normalized === '') {
            return 'Pendiente';
        }

        $statusMap = [
            'pending' => 'Pendiente',
            'processing' => 'En proceso',
            'in_process' => 'En proceso',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            'canceled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'failed' => 'Fallido',
        ];

        $paymentMap = [
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'verified' => 'Verificado',
            'approved' => 'Aprobado',
            'failed' => 'Fallido',
            'rejected' => 'Rechazado',
            'cancelled' => 'Cancelado',
            'canceled' => 'Cancelado',
            'refunded' => 'Reembolsado',
        ];

        $map = $context === 'payment_status' ? $paymentMap : $statusMap;

        if (array_key_exists($normalized, $map)) {
            return $map[$normalized];
        }

        return Str::title(str_replace(['_', '-'], ' ', $normalized));
    }

    private function formatBogotaDate(mixed $value): string
    {
        $date = $this->parseDateAsBogota($value);
        if (!$date) {
            return 'No disponible';
        }

        return $date->format('d/m/Y H:i');
    }

    private function parseDateAsBogota(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->timezone('America/Bogota');
        }

        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            $hasOffset = preg_match('/([+-]\d{2}:?\d{2}|Z)$/i', $raw) === 1;
            $date = $hasOffset ? Carbon::parse($raw) : Carbon::parse($raw, 'UTC');
            return $date->timezone('America/Bogota');
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveStoreUrl(): string
    {
        $url = trim((string) config('services.frontend.store_url', 'http://localhost:5173'));
        if ($url === '') {
            return 'http://localhost:5173';
        }

        return rtrim($url, '/');
    }

    private function normalizeInvoiceFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => Str::lower(trim((string) ($filters['status'] ?? ''))),
            'payment_status' => Str::lower(trim((string) ($filters['payment_status'] ?? ''))),
            'from_date' => trim((string) ($filters['from_date'] ?? '')),
            'to_date' => trim((string) ($filters['to_date'] ?? '')),
            'source' => Str::lower(trim((string) ($filters['source'] ?? ''))),
        ];
    }

    private function sanitizeFileSegment(string $value): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9_-]/', '_', $value) ?: 'factura';
        return trim($cleaned, '_');
    }

    private function likeOperator(?string $connection = null): string
    {
        try {
            $driver = ($connection ? DB::connection($connection) : DB::connection())->getDriverName();
            return $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
        } catch (Throwable) {
            return 'LIKE';
        }
    }

    private function query(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }

    private function hasTable(string $table, ?string $connection): bool
    {
        try {
            return Schema::connection($this->resolveConnectionName($connection))->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function firstExistingColumn(string $table, array $candidates, ?string $connection): ?string
    {
        $connectionName = $this->resolveConnectionName($connection);

        try {
            foreach ($candidates as $column) {
                if (Schema::connection($connectionName)->hasColumn($table, $column)) {
                    return $column;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function resolveConnectionName(?string $connection): string
    {
        return $connection ?: config('database.default');
    }
}
