<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Controlador de administración para funcionalidades del panel de control (dashboard).
 * Centraliza la lógica de consulta, fusión y enriquecimiento de órdenes
 * provenientes de las bases de datos del microservicio y del sistema heredado (legacy).
 */
class AdminOrderController extends Controller
{
    /** Conexión a la base de datos del sistema heredado. */
    private const LEGACY_CONNECTION = 'legacy_mysql';
    /**
     * Agrupaciones de estados para filtrar órdenes en el panel admin.
     * Cada clave agrupa variantes de un mismo estado lógico.
     */
    private const ADMIN_STATUS_FILTER_GROUPS = [
        'pending' => ['pending', 'created', 'pending_payment'],
        'processing' => ['processing', 'in_review', 'en_revision'],
        'cancelled' => ['cancelled', 'canceled', 'refunded'],
    ];

    /**
     * Retorna el operador SQL «LIKE» correspondiente al motor de base de datos.
     * PostgreSQL usa «ILIKE» (case-insensitive); MySQL/MariaDB usan «LIKE».
     */
    private function likeOperator(?string $connection = null): string
    {
        $driver = ($connection ? DB::connection($connection) : DB::connection())->getDriverName();
        return $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    /**
     * Devuelve el nombre de la primera columna existente en una tabla,
     * evaluando una lista de nombres candidatos. Útil para mantener
     * compatibilidad entre esquemas de base de datos (microservicio vs. legacy).
     */
    private function firstExistingColumn(string $table, array $candidates, ?string $connection = null): ?string
    {
        $dbConnection = $connection ?: config('database.default');

        foreach ($candidates as $column) {
            if (Schema::connection($dbConnection)->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Expande un valor de filtro de estado a su grupo de variantes
     * según ADMIN_STATUS_FILTER_GROUPS. Si no hay grupo, retorna el valor original.
     */
    private function expandAdminStatusFilterValues(?string $status): array
    {
        $normalizedStatus = Str::of((string) $status)->trim()->lower()->replace('-', '_')->value();

        // Si el estado está vacío, retornamos un arreglo vacío
        if ($normalizedStatus === '') {
            return [];
        }

        return self::ADMIN_STATUS_FILTER_GROUPS[$normalizedStatus] ?? [$normalizedStatus];
    }

    /**
     * Órdenes recientes para el dashboard del panel de administración.
     * Consulta, fusiona y enriquece datos desde microservicio y sistema heredado,
     * retornando un subconjunto de filas junto con estadísticas resumidas.
     */
    public function recentOrders(Request $request): JsonResponse
    {
        // Limitamos entre 1 y 500; el límite interno para estadísticas es mayor
        $limit = max(1, min((int) $request->input('limit', 12), 500));
        $statsLimit = max($limit, 1000);

        // Obtenemos filas desde ambas fuentes (microservicio y legacy)
        $distributedRows = $this->fetchAdminOrdersRows(null, $request, $statsLimit);
        $legacyRows = $this->fetchAdminOrdersRows(self::LEGACY_CONNECTION, $request, $statsLimit);
        // Fusionamos y enriquecemos con datos de cliente
        $mergedRows = $this->enrichAdminOrdersWithCustomerData(
            $this->mergeAdminOrderRows($distributedRows, $legacyRows)
        );

        // Solo devolvemos la cantidad solicitada al frontend
        $rows = $mergedRows
            ->take($limit)
            ->values();

        // Estadísticas globales sobre el conjunto completo de órdenes
        $totalOrders = $mergedRows->count();
        $totalRevenue = $mergedRows->sum(static fn ($row) => (float) ($row->total ?? 0));
        // Contamos órdenes pendientes (no finalizadas)
        $pendingOrders = $mergedRows->filter(static function ($row): bool {
            $status = strtolower((string) ($row->status ?? $row->order_status ?? 'pending'));
            return in_array($status, ['created', 'pending', 'pending_payment', 'in_review'], true);
        })->count();
        // Contamos órdenes completadas (entregadas)
        $completedOrders = $mergedRows->filter(static function ($row): bool {
            $status = strtolower((string) ($row->status ?? $row->order_status ?? 'pending'));
            return in_array($status, ['delivered', 'completed'], true);
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
                'stats' => [
                    'total_orders' => $totalOrders,
                    'total_revenue' => round((float) $totalRevenue, 2),
                    'pending_orders' => $pendingOrders,
                    'completed_orders' => $completedOrders,
                ],
            ],
        ]);
    }

    /**
     * Lista solicitudes de reembolso creadas por clientes y las une con
     * la orden asociada para que el panel admin tenga un flujo trazable.
     */
    public function refundRequests(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:24'],
            'search' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $limit = max(1, min((int) ($data['limit'] ?? 200), 500));
        $status = $this->nullableString($data['status'] ?? null);
        $search = $this->nullableString($data['search'] ?? null);

        // Reutiliza la misma lectura doble del admin de órdenes, pero solo conserva conexiones con tabla de solicitudes.
        $rows = $this->fetchRefundRequestRows(null, $status, $search, $limit)
            ->concat($this->fetchRefundRequestRows(self::LEGACY_CONNECTION, $status, $search, $limit))
            ->sortByDesc(static fn ($row) => strtotime((string) ($row->requested_at ?? $row->created_at ?? '')) ?: 0)
            ->take($limit)
            ->values();

        $stats = [
            'total' => $rows->count(),
            'requested' => $rows->where('status', 'requested')->count(),
            'in_process' => $rows->filter(static fn ($row) => in_array((string) $row->status, ['approved', 'processing'], true))->count(),
            'completed' => $rows->where('status', 'completed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Actualiza la solicitud de reembolso y sincroniza el estado de pago
     * de la orden para mantener un recorrido operativo consistente.
     */
    public function updateRefundRequest(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['approved', 'processing', 'rejected', 'completed'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'changed_by' => ['nullable', 'string', 'max:40'],
            'changed_by_name' => ['nullable', 'string', 'max:100'],
        ]);

        $connection = $this->normalizeSourceConnection($data['source'] ?? null);
        if (!$this->hasRefundRequestsTable($connection)) {
            return response()->json(['message' => 'La sección de reembolsos no está disponible.'], 422);
        }

        $refundRequest = $this->query($connection)
            ->table('order_refund_requests')
            ->where('id', $id)
            ->first();

        if (!$refundRequest) {
            return response()->json(['message' => 'Solicitud de reembolso no encontrada.'], 404);
        }

        $order = $this->query($connection)
            ->table('orders')
            ->where('id', (int) $refundRequest->order_id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Orden asociada no encontrada.'], 404);
        }

        $targetStatus = (string) $data['status'];
        $now = now();
        $paymentStatus = $this->paymentStatusForRefundStatus($targetStatus);
        $paymentStatusColumn = $this->firstExistingColumn('orders', ['payment_status'], $connection);
        $oldPaymentStatus = $paymentStatusColumn ? ($order->{$paymentStatusColumn} ?? null) : null;
        $oldRefundStatus = (string) ($refundRequest->status ?? 'requested');

        $this->query($connection)->transaction(function () use ($connection, $id, $refundRequest, $targetStatus, $now, $paymentStatusColumn, $paymentStatus, $oldPaymentStatus, $oldRefundStatus, $data): void {
            $this->query($connection)
                ->table('order_refund_requests')
                ->where('id', $id)
                ->update([
                    'status' => $targetStatus,
                    'resolved_at' => in_array($targetStatus, ['rejected', 'completed'], true) ? $now : null,
                    'updated_at' => $now,
                ]);

            if ($paymentStatusColumn !== null && $paymentStatus !== null) {
                $this->query($connection)
                    ->table('orders')
                    ->where('id', (int) $refundRequest->order_id)
                    ->update([
                        $paymentStatusColumn => $paymentStatus,
                        'updated_at' => $now,
                    ]);
            }

            $this->insertOrderHistory($connection, [
                'order_id' => (int) $refundRequest->order_id,
                'changed_by' => $this->nullableString($data['changed_by'] ?? null),
                'changed_by_name' => $this->nullableString($data['changed_by_name'] ?? null) ?? 'Administrador',
                'change_type' => 'refund_request',
                'field_changed' => 'refund_status',
                'old_value' => $oldRefundStatus,
                'new_value' => $targetStatus,
                'description' => $this->buildRefundHistoryDescription($targetStatus, $data['description'] ?? null),
                'created_at' => $now,
            ]);

            if ($paymentStatusColumn !== null && $paymentStatus !== null && !$this->sameNormalizedValue($oldPaymentStatus, $paymentStatus)) {
                $this->insertOrderHistory($connection, [
                    'order_id' => (int) $refundRequest->order_id,
                    'changed_by' => $this->nullableString($data['changed_by'] ?? null),
                    'changed_by_name' => $this->nullableString($data['changed_by_name'] ?? null) ?? 'Administrador',
                    'change_type' => 'payment_change',
                    'field_changed' => 'payment_status',
                    'old_value' => $oldPaymentStatus,
                    'new_value' => $paymentStatus,
                    'description' => 'Estado de pago sincronizado desde administración de reembolsos.',
                    'created_at' => $now,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de reembolso actualizada.',
            'data' => [
                'id' => $id,
                'status' => $targetStatus,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    /**
     * Reporte de ventas para el panel de administración.
     * Agrupa órdenes por estado, por día y por método de pago,
     * calculando ingresos, envíos y descuentos.
     */
    public function reportSales(Request $request): JsonResponse
    {
        // Mapeamos los parámetros de la solicitud al formato que esperan los métodos internos
        $from = $request->input('from');
        $to   = $request->input('to');
        $filtersRequest = new Request([
            'from_date' => $from,
            'to_date' => $to,
            'status' => $request->input('status'),
        ]);

        // Consultamos órdenes desde ambas fuentes y las fusionamos
        $distributedRows = $this->fetchAnalyticsOrdersRows(null, $filtersRequest);
        $legacyRows = $this->fetchAnalyticsOrdersRows(self::LEGACY_CONNECTION, $filtersRequest);
        $orders = $this->mergeAdminOrderRows($distributedRows, $legacyRows);

        // Métricas globales del reporte
        $totalOrders = $orders->count();
        $totalRevenue = round($orders->sum(fn ($row) => $this->orderTotalValue($row)), 2);
        $totalShipping = round($orders->sum(fn ($row) => $this->orderShippingValue($row)), 2);
        $totalDiscount = round($orders->sum(fn ($row) => $this->orderDiscountValue($row)), 2);
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        // Agrupación por estado normalizado
        $byStatus = $orders
            ->groupBy(fn ($row) => $this->normalizeReportStatus($row->status ?? $row->order_status ?? null))
            ->map(fn ($rows, $status) => [
                'status' => $status,
                'count' => $rows->count(),
                'revenue' => round($rows->sum(fn ($row) => $this->orderTotalValue($row)), 2),
            ])
            ->values();

        // Ventas diarias: agrupamos por fecha y calculamos métricas por día
        $dailySales = $orders
            ->groupBy(fn ($row) => $this->reportDateKey($row))
            ->reject(fn ($rows, $date) => $date === null || $date === '')
            ->map(function ($rows, $date) {
                $ordersCount = $rows->count();
                $revenue = round($rows->sum(fn ($row) => $this->orderTotalValue($row)), 2);

                return [
                    'date' => $date,
                    'orders' => $ordersCount,
                    'subtotal' => round($rows->sum(fn ($row) => $this->orderSubtotalValue($row)), 2),
                    'shipping' => round($rows->sum(fn ($row) => $this->orderShippingValue($row)), 2),
                    'discount' => round($rows->sum(fn ($row) => $this->orderDiscountValue($row)), 2),
                    'revenue' => $revenue,
                    'avg_order_value' => $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0,
                ];
            })
            ->sortBy('date')
            ->values();

        $rows = $dailySales
            ->map(static fn (array $row) => [...$row, 'products' => 0])
            ->values();

        // Agrupación por método de pago
        $byPaymentMethod = $orders
            ->groupBy(fn ($row) => trim((string) ($row->payment_method ?? '')))
            ->reject(fn ($rows, $paymentMethod) => $paymentMethod === '')
            ->map(fn ($rows, $paymentMethod) => [
                'payment_method' => $paymentMethod,
                'count' => $rows->count(),
                'revenue' => round($rows->sum(fn ($row) => $this->orderTotalValue($row)), 2),
            ])
            ->values();

        // Compatibilidad: se devuelven claves snake_case y camelCase.
        $payload = [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'total_shipping' => $totalShipping,
            'total_discount' => $totalDiscount,
            'total_products' => 0,
            'by_status' => $byStatus,
            'daily_sales' => $dailySales,
            'by_payment_method' => $byPaymentMethod,
            'rows' => $rows,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'avgOrderValue' => $avgOrderValue,
            'totalShipping' => $totalShipping,
            'totalDiscount' => $totalDiscount,
            'totalProducts' => 0,
        ];

        return response()->json([
            'success' => true,
            'data'    => $payload,
        ]);
    }

    /**
     * Reporte de productos populares basado en órdenes reales.
     * Agrupa los items de órdenes por producto y calcula métricas
     * como cantidad vendida, ingresos generados y primera/última venta.
     */
    public function reportProducts(Request $request): JsonResponse
    {
        $limit = max(5, min((int) $request->input('limit', 50), 100));
        $filtersRequest = new Request([
            'from_date' => $request->input('from'),
            'to_date' => $request->input('to'),
        ]);

        // Consultamos items desde ambas fuentes
        $distributedRows = $this->fetchAnalyticsOrderItemRows(null, $filtersRequest);
        $legacyRows = $this->fetchAnalyticsOrderItemRows(self::LEGACY_CONNECTION, $filtersRequest);

        // Fusionamos y agrupamos por ID de producto
        $rows = $this->mergeAnalyticsOrderItemRows($distributedRows, $legacyRows)
            ->groupBy(static fn ($row) => (int) ($row->product_id ?? 0))
            ->filter(static fn ($items, $productId) => (int) $productId > 0)  // Descartamos productos sin ID válido
            ->map(function ($items, $productId) {
                // Elegimos el item con mayor cantidad de información
                $bestItem = $items
                    ->sortByDesc(fn ($row) => $this->orderItemInformationScore($row))
                    ->first();

                $name = trim((string) ($bestItem->product_name ?? ''));
                // Calculamos en cuántas órdenes diferentes apareció este producto
                $timesSold = $items
                    ->map(static fn ($row) => trim((string) ($row->order_merge_key ?? '')))
                    ->filter(static fn ($mergeKey) => $mergeKey !== '')
                    ->unique()
                    ->count();

                $firstOrderAt = $items
                    ->map(static fn ($row) => $row->created_at ?? null)
                    ->filter()
                    ->sort()
                    ->first();

                $lastOrderAt = $items
                    ->map(static fn ($row) => $row->created_at ?? null)
                    ->filter()
                    ->sortDesc()
                    ->first();

                $avgPrice = $items->avg(static fn ($row) => (float) ($row->price ?? 0)) ?? 0;
                $totalQuantity = (int) $items->sum(static fn ($row) => (int) ($row->quantity ?? 0));
                $totalRevenue = (float) $items->sum(static fn ($row) => (float) ($row->total ?? 0));

                return [
                    'id' => (int) $productId,
                    'product_id' => (int) $productId,
                    'name' => $name !== '' ? $name : 'Producto #' . $productId,
                    'times_sold' => $timesSold,
                    'total_quantity' => $totalQuantity,
                    'units_sold' => $totalQuantity,
                    'avg_price' => round($avgPrice, 2),
                    'total_revenue' => round($totalRevenue, 2),
                    'revenue' => round($totalRevenue, 2),
                    'first_order_at' => $firstOrderAt,
                    'last_order_at' => $lastOrderAt,
                ];
            })
            ->sortByDesc('total_revenue')  // Ordenamos por ingresos, de mayor a menor
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * Reporte de clientes recurrentes basado en órdenes reales.
     * Identifica y agrupa clientes por ID de usuario, email o clave de orden,
     * calculando frecuencia de compra, valor total gastado y segmentación.
     */
    public function reportCustomers(Request $request): JsonResponse
    {
        $minOrders = max(1, min((int) $request->input('min_orders', 2), 50));
        $filtersRequest = new Request([
            'from_date' => $request->input('from'),
            'to_date' => $request->input('to'),
        ]);

        // Obtenemos órdenes enriquecidas con datos de clientes desde ambas fuentes
        $orders = $this->enrichAdminOrdersWithCustomerData(
            $this->mergeAdminOrderRows(
                $this->fetchAnalyticsOrdersRows(null, $filtersRequest),
                $this->fetchAnalyticsOrdersRows(self::LEGACY_CONNECTION, $filtersRequest)
            )
        );

        $customers = [];
        $customerKeyByUserId = [];  // Índice: userId → customerKey
        $customerKeyByEmail = [];   // Índice: email → customerKey

        foreach ($orders as $order) {
            // Extraemos datos del cliente desde múltiples columnas posibles
            $userId = trim((string) ($order->user_id ?? ''));
            $name = trim((string) ($order->user_name ?? $order->customer_name ?? $order->billing_name ?? ''));
            $email = strtolower(trim((string) ($order->user_email ?? $order->customer_email ?? $order->billing_email ?? '')));
            $phone = trim((string) ($order->user_phone ?? $order->customer_phone ?? $order->billing_phone ?? $order->phone ?? ''));

            $customerKey = null;

            // Priorizamos vinculación por userId, luego por email, luego creamos clave basada en orden
            if ($userId !== '' && array_key_exists($userId, $customerKeyByUserId)) {
                $customerKey = $customerKeyByUserId[$userId];
            } elseif ($email !== '' && array_key_exists($email, $customerKeyByEmail)) {
                $customerKey = $customerKeyByEmail[$email];
            } elseif ($userId !== '') {
                $customerKey = 'id:' . $userId;
            } elseif ($email !== '') {
                $customerKey = 'email:' . $email;
            } else {
                // Cliente anónimo: usamos la clave de fusión de la orden
                $customerKey = 'guest:' . $this->resolveOrderMergeKey($order);
            }

            // Inicializamos el registro del cliente si es la primera vez que lo vemos
            if (!isset($customers[$customerKey])) {
                $customers[$customerKey] = [
                    'id' => $userId !== '' ? $userId : $customerKey,
                    'user_id' => $userId !== '' ? $userId : null,
                    'name' => $name !== '' ? $name : 'Cliente sin nombre',
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'orders_count' => 0,
                    'total_spent' => 0.0,
                    'first_order' => null,
                    'last_order' => null,
                ];
            }

            // Actualizamos userId si previamente no tenía y ahora sí
            if ($customers[$customerKey]['user_id'] === null && $userId !== '') {
                $customers[$customerKey]['user_id'] = $userId;
                $customers[$customerKey]['id'] = $userId;
            }

            // Actualizamos el nombre si el actual es un valor placeholder
            if (($customers[$customerKey]['name'] === null
                    || trim((string) $customers[$customerKey]['name']) === ''
                    || in_array(Str::lower(trim((string) $customers[$customerKey]['name'])), ['cliente sin nombre', 'cliente', 'sin nombre'], true))
                && $name !== '') {
                $customers[$customerKey]['name'] = $name;
            }

            // Completamos email y teléfono si están vacíos
            if ($customers[$customerKey]['email'] === null && $email !== '') {
                $customers[$customerKey]['email'] = $email;
            }

            if ($customers[$customerKey]['phone'] === null && $phone !== '') {
                $customers[$customerKey]['phone'] = $phone;
            }

            // Registramos índices para futuras órdenes del mismo cliente
            if ($userId !== '') {
                $customerKeyByUserId[$userId] = $customerKey;
            }

            if ($email !== '') {
                $customerKeyByEmail[$email] = $customerKey;
            }

            // Acumulamos conteo y gasto total
            $customers[$customerKey]['orders_count']++;
            $customers[$customerKey]['total_spent'] += $this->orderTotalValue($order);

            // Actualizamos fechas de primera y última orden
            $createdAt = $order->created_at ? Carbon::parse($order->created_at) : null;
            if ($createdAt) {
                if ($customers[$customerKey]['first_order'] === null || $createdAt->lt(Carbon::parse($customers[$customerKey]['first_order']))) {
                    $customers[$customerKey]['first_order'] = $createdAt->toDateTimeString();
                }

                if ($customers[$customerKey]['last_order'] === null || $createdAt->gt(Carbon::parse($customers[$customerKey]['last_order']))) {
                    $customers[$customerKey]['last_order'] = $createdAt->toDateTimeString();
                }
            }
        }

        // Transformamos el arreglo asociativo en una colección con métricas calculadas
        $rows = collect($customers)
            ->map(static function (array $customer) {
                $lastOrder = $customer['last_order'] ? Carbon::parse($customer['last_order']) : null;
                $avgOrderValue = $customer['orders_count'] > 0 ? $customer['total_spent'] / $customer['orders_count'] : 0;

                return [
                    ...$customer,
                    'total_spent' => round((float) $customer['total_spent'], 2),
                    'avg_order_value' => round((float) $avgOrderValue, 2),
                    'lifetime_value' => round((float) $customer['total_spent'], 2),
                    'customer_age_days' => $lastOrder ? $lastOrder->diffInDays(now()) : null,
                    'last_order_date' => $customer['last_order'],
                ];
            })
            ->sortByDesc('total_spent')
            ->values();

        // Segmentación por cantidad de órdenes
        $distribution = [
            ['segment' => '1 orden', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] === 1)->count()],
            ['segment' => '2-5 órdenes', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] >= 2 && (int) $row['orders_count'] <= 5)->count()],
            ['segment' => '6-10 órdenes', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] >= 6 && (int) $row['orders_count'] <= 10)->count()],
            ['segment' => 'Más de 10 órdenes', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] > 10)->count()],
        ];

        // Filtramos clientes que cumplan con el mínimo de órdenes solicitado
        $filteredRows = $rows
            ->filter(fn ($row) => (int) $row['orders_count'] >= $minOrders)
            ->values();

        $stats = [
            'customers_with_orders' => $rows->count(),
            'returning_customers' => $rows->filter(fn ($row) => (int) $row['orders_count'] >= 2)->count(),
            'avg_orders_per_customer' => $rows->count() > 0
                ? round($rows->avg(fn ($row) => (int) $row['orders_count']), 1)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'distribution' => $distribution,
                'rows' => $filteredRows,
                'top_customers' => $rows->take(10)->values(),
            ],
        ]);
    }

    /**
     * Construye la consulta para analytics de órdenes.
     * Extiende buildOrdersQuery() excluyendo automáticamente las órdenes canceladas
     * a menos que se especifique un filtro de estado explícito.
     * Reutiliza la lógica de buildOrdersQuery() en este mismo archivo.
     */
    private function buildAnalyticsOrdersQuery(?string $connection, Request $request)
    {
        $query = $this->buildOrdersQuery($connection, $request);
        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';

        // Si no hay filtro de estado, excluimos cancelados por defecto
        if (!$request->filled('status')) {
            $query->whereNotIn($statusColumn, self::ADMIN_STATUS_FILTER_GROUPS['cancelled']);
        }

        return $query;
    }

    /**
     * Construye la consulta para analytics de items de órdenes.
     * Hace JOIN con la tabla orders para aplicar filtros por fecha y estado.
     */
    private function buildAnalyticsOrderItemsQuery(?string $connection, Request $request)
    {
        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';
        $query = $this->query($connection)
            ->table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id');

        // Filtro por fecha de creación (desde)
        if ($request->filled('from_date')) {
            $query->where('o.created_at', '>=', $request->string('from_date')->toString());
        }

        // Filtro por fecha de creación (hasta, inclusive)
        if ($request->filled('to_date')) {
            $query->where('o.created_at', '<=', $request->string('to_date')->toString() . ' 23:59:59');
        }

        // Filtro por estado; si no se especifica, excluimos cancelados
        if ($request->filled('status')) {
            $query->where('o.' . $statusColumn, $request->string('status')->toString());
        } else {
            $query->whereNotIn('o.' . $statusColumn, self::ADMIN_STATUS_FILTER_GROUPS['cancelled']);
        }

        return $query;
    }

    /**
     * Ejecuta la consulta de analytics de órdenes y retorna la colección de resultados.
     * Etiqueta cada fila con su origen (microservicio o legacy) para la fusión posterior.
     * En caso de error, retorna una colección vacía para no interrumpir el reporte.
     */
    private function fetchAnalyticsOrdersRows(?string $connection, Request $request): \Illuminate\Support\Collection
    {
        try {
            $source = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';

            return $this->buildAnalyticsOrdersQuery($connection, $request)
                ->orderByDesc('created_at')
                ->get()
                ->map(static function ($row) use ($source) {
                    $row->order_source = $source;
                    return $row;
                })
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Ejecuta la consulta de analytics de items de órdenes y retorna la colección.
     * Resuelve dinámicamente los nombres de columna según el esquema de cada conexión
     * y asigna una clave de fusión (order_merge_key) para la deduplicación posterior.
     * En caso de error, retorna una colección vacía.
     */
    private function fetchAnalyticsOrderItemRows(?string $connection, Request $request): \Illuminate\Support\Collection
    {
        try {
            $source = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';
            // Resolvemos nombres de columna según el esquema disponible
            $productNameColumn = $this->firstExistingColumn('order_items', ['product_name', 'name'], $connection) ?: 'product_name';
            $variantNameColumn = $this->firstExistingColumn('order_items', ['variant_name'], $connection);
            $orderNumberColumn = $this->firstExistingColumn('orders', ['order_number'], $connection);

            $query = $this->buildAnalyticsOrderItemsQuery($connection, $request)
                ->select(
                    'oi.order_id',
                    'oi.product_id',
                    'oi.quantity',
                    'oi.price',
                    'oi.total',
                    'o.created_at',
                    'o.updated_at'
                )
                ->selectRaw("oi.{$productNameColumn} as product_name");

            // Agregamos variant_name si la columna existe; si no, devolvemos NULL
            if ($variantNameColumn !== null) {
                if ($variantNameColumn === 'variant_name') {
                    $query->addSelect('oi.variant_name');
                } else {
                    $query->selectRaw("oi.{$variantNameColumn} as variant_name");
                }
            } else {
                $query->selectRaw('NULL as variant_name');
            }

            // Agregamos order_number si la columna existe; si no, devolvemos NULL
            if ($orderNumberColumn !== null) {
                if ($orderNumberColumn === 'order_number') {
                    $query->addSelect('o.order_number');
                } else {
                    $query->selectRaw("o.{$orderNumberColumn} as order_number");
                }
            } else {
                $query->selectRaw('NULL as order_number');
            }

            return $query
                ->get()
                ->map(function ($row) use ($source) {
                    $row->order_source = $source;
                    // Generamos clave de fusión para deduplicación entre fuentes
                    $row->order_merge_key = $this->resolveOrderMergeKey((object) [
                        'order_number' => $row->order_number ?? null,
                        'id' => $row->order_id ?? null,
                        'order_source' => $source,
                    ]);

                    return $row;
                })
                ->values();
        } catch (Throwable) {
            // Si falla la conexión, retornamos colección vacía para no bloquear el reporte
            return collect();
        }
    }

    /**
     * Obtiene filas de órdenes para el dashboard admin desde una conexión específica.
     * Aplica los filtros de la solicitud y limita los resultados.
     * Etiqueta cada fila con su origen (microservicio o legacy).
     */
    private function fetchAdminOrdersRows(?string $connection, Request $request, int $limit): \Illuminate\Support\Collection
    {
        try {
            $source = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';

            return $this->buildOrdersQuery($connection, $request)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(static function ($row) use ($source) {
                    $row->order_source = $source;  // Marcamos el origen para la fusión
                    return $row;
                })
                ->values();
        } catch (Throwable) {
            // Si la conexión falla, devolvemos colección vacía
            return collect();
        }
    }

    /**
     * Obtiene solicitudes de reembolso desde la conexión indicada y las
     * enriquece con datos básicos de la orden asociada.
     */
    private function fetchRefundRequestRows(?string $connection, ?string $status, ?string $search, int $limit): \Illuminate\Support\Collection
    {
        if (!$this->hasRefundRequestsTable($connection)) {
            return collect();
        }

        try {
            $source = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';
            $likeOperator = $this->likeOperator($connection);
            $statusCol = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection);
            $paymentStatusCol = $this->firstExistingColumn('orders', ['payment_status'], $connection);
            $customerNameCol = $this->firstExistingColumn('orders', ['user_name', 'customer_name', 'billing_name'], $connection);
            $customerEmailCol = $this->firstExistingColumn('orders', ['user_email', 'customer_email', 'billing_email'], $connection);

            $query = $this->query($connection)
                ->table('order_refund_requests as refunds')
                ->leftJoin('orders', 'orders.id', '=', 'refunds.order_id')
                ->select([
                    'refunds.*',
                    'orders.order_number',
                    'orders.total',
                    'orders.created_at as order_created_at',
                ]);

            if ($statusCol) {
                $query->addSelect("orders.{$statusCol} as order_status");
            }

            if ($paymentStatusCol) {
                $query->addSelect("orders.{$paymentStatusCol} as payment_status");
            }

            if ($customerNameCol) {
                $query->addSelect("orders.{$customerNameCol} as customer_name");
            }

            if ($customerEmailCol) {
                $query->addSelect("orders.{$customerEmailCol} as customer_email");
            }

            if ($status !== null) {
                $query->where('refunds.status', $status);
            }

            if ($search !== null) {
                $query->where(function ($searchQuery) use ($search, $customerNameCol, $customerEmailCol, $likeOperator): void {
                    $searchQuery
                        ->where('orders.order_number', $likeOperator, "%{$search}%")
                        ->orWhere('refunds.reason', $likeOperator, "%{$search}%")
                        ->orWhere('refunds.user_email', $likeOperator, "%{$search}%");

                    if ($customerNameCol) {
                        $searchQuery->orWhere("orders.{$customerNameCol}", $likeOperator, "%{$search}%");
                    }

                    if ($customerEmailCol) {
                        $searchQuery->orWhere("orders.{$customerEmailCol}", $likeOperator, "%{$search}%");
                    }
                });
            }

            return $query
                ->orderByDesc('refunds.requested_at')
                ->limit($limit)
                ->get()
                ->map(static function ($row) use ($source) {
                    $row->source = $source;
                    $row->evidence_url = trim((string) ($row->evidence_path ?? ''));
                    $row->customer_name = trim((string) ($row->customer_name ?? '')) ?: 'Cliente';
                    $row->customer_email = trim((string) ($row->customer_email ?? $row->user_email ?? ''));
                    $row->total = (float) ($row->total ?? 0);

                    return $row;
                })
                ->values();
        } catch (Throwable $exception) {
            Log::warning('No se pudieron consultar solicitudes de reembolso admin.', [
                'source' => $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice',
                'error' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Verifica si la tabla de solicitudes existe en la conexión objetivo.
     */
    private function hasRefundRequestsTable(?string $connection): bool
    {
        try {
            return Schema::connection($this->resolveConnectionName($connection))->hasTable('order_refund_requests');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Normaliza el origen recibido desde el frontend al nombre de conexión usado internamente.
     */
    private function normalizeSourceConnection(?string $source): ?string
    {
        return Str::lower(trim((string) $source)) === 'legacy' ? self::LEGACY_CONNECTION : null;
    }

    /**
     * Convierte una conexión nula en el nombre real configurado para consultas Schema.
     */
    private function resolveConnectionName(?string $connection): string
    {
        return $connection ?: config('database.default');
    }

    /**
     * Relaciona cada estado administrativo de reembolso con el estado de pago visible de la orden.
     */
    private function paymentStatusForRefundStatus(string $status): ?string
    {
        return match ($status) {
            'approved', 'processing' => 'pending_refund',
            'completed' => 'refunded',
            'rejected' => 'verified',
            default => null,
        };
    }

    /**
     * Construye la descripción de historial y conserva la nota del operador cuando exista.
     */
    private function buildRefundHistoryDescription(string $status, mixed $description): string
    {
        $labels = [
            'approved' => 'Solicitud de reembolso aceptada.',
            'processing' => 'Reembolso marcado en proceso.',
            'rejected' => 'Solicitud de reembolso rechazada.',
            'completed' => 'Reembolso completado.',
        ];

        $base = $labels[$status] ?? 'Solicitud de reembolso actualizada.';
        $note = $this->nullableString($description);

        return $note === null ? $base : "{$base} Nota: {$note}";
    }

    /**
     * Inserta historial de orden sin romper el flujo si el entorno no tiene la tabla.
     */
    private function insertOrderHistory(?string $connection, array $payload): void
    {
        try {
            if (!Schema::connection($this->resolveConnectionName($connection))->hasTable('order_status_history')) {
                return;
            }

            $this->query($connection)->table('order_status_history')->insert($payload);
        } catch (Throwable) {
            // El historial no debe bloquear la operación administrativa principal.
        }
    }

    /**
     * Fusiona dos colecciones de órdenes (microservicio + legacy) en una sola,
     * deduplicando por clave de fusión. Cuando hay duplicados, conserva la fila
     * con mayor puntaje de información (orderRowInformationScore) y, en caso
     * de empate, la más recientemente actualizada.
     * Las filas resultantes se ordenan por created_at descendente.
     * @see self::resolveOrderMergeKey()
     * @see self::orderRowInformationScore()
     */
    private function mergeAdminOrderRows(\Illuminate\Support\Collection $distributedRows, \Illuminate\Support\Collection $legacyRows): \Illuminate\Support\Collection
    {
        $bestRowsByKey = [];  // Mapa: mergeKey → mejor fila

        // Concatenamos ambas colecciones y procesamos cada fila
        foreach ($distributedRows->concat($legacyRows) as $row) {
            $mergeKey = $this->resolveOrderMergeKey($row);

            // Si es la primera vez que vemos esta clave, la guardamos directamente
            if (!array_key_exists($mergeKey, $bestRowsByKey)) {
                $bestRowsByKey[$mergeKey] = $row;
                continue;
            }

            // Comparamos con la fila existente por puntaje de información
            $currentRow = $bestRowsByKey[$mergeKey];
            $currentScore = $this->orderRowInformationScore($currentRow);
            $candidateScore = $this->orderRowInformationScore($row);

            // La fila con más información reemplaza a la actual
            if ($candidateScore > $currentScore) {
                $bestRowsByKey[$mergeKey] = $row;
                continue;
            }

            // A mismo puntaje, conservamos la más recientemente actualizada
            if ($candidateScore === $currentScore) {
                $currentTimestamp = strtotime((string) ($currentRow->updated_at ?? $currentRow->created_at ?? '')) ?: 0;
                $candidateTimestamp = strtotime((string) ($row->updated_at ?? $row->created_at ?? '')) ?: 0;

                if ($candidateTimestamp > $currentTimestamp) {
                    $bestRowsByKey[$mergeKey] = $row;
                }
            }
        }

        // Ordenamos el resultado por fecha de creación descendente
        return collect(array_values($bestRowsByKey))
            ->sortByDesc(static function ($row): int {
                $rawDate = $row->created_at ?? null;
                $timestamp = is_string($rawDate) ? strtotime($rawDate) : null;
                return $timestamp ?: 0;
            })
            ->values();
    }

    /**
     * Fusiona dos colecciones de items de órdenes (microservicio + legacy)
     * usando la misma estrategia que mergeAdminOrderRows(), pero aplicando
     * orderItemInformationScore para resolver conflictos.
     * @see self::mergeAdminOrderRows()
     * @see self::orderItemInformationScore()
     */
    private function mergeAnalyticsOrderItemRows(\Illuminate\Support\Collection $distributedRows, \Illuminate\Support\Collection $legacyRows): \Illuminate\Support\Collection
    {
        $bestRowsByKey = [];

        // Misma lógica de deduplicación que mergeAdminOrderRows pero con claves de item
        foreach ($distributedRows->concat($legacyRows) as $row) {
            $mergeKey = $this->resolveOrderItemMergeKey($row);

            // Primera aparición: la guardamos
            if (!array_key_exists($mergeKey, $bestRowsByKey)) {
                $bestRowsByKey[$mergeKey] = $row;
                continue;
            }

            // Comparamos puntaje de información y fecha
            $currentRow = $bestRowsByKey[$mergeKey];
            $currentScore = $this->orderItemInformationScore($currentRow);
            $candidateScore = $this->orderItemInformationScore($row);

            if ($candidateScore > $currentScore) {
                $bestRowsByKey[$mergeKey] = $row;
                continue;
            }

            // En caso de empate, gana la más reciente
            if ($candidateScore === $currentScore) {
                $currentTimestamp = strtotime((string) ($currentRow->updated_at ?? $currentRow->created_at ?? '')) ?: 0;
                $candidateTimestamp = strtotime((string) ($row->updated_at ?? $row->created_at ?? '')) ?: 0;

                if ($candidateTimestamp > $currentTimestamp) {
                    $bestRowsByKey[$mergeKey] = $row;
                }
            }
        }

        return collect(array_values($bestRowsByKey))
            ->sortByDesc(static function ($row): int {
                $rawDate = $row->created_at ?? null;
                $timestamp = is_string($rawDate) ? strtotime($rawDate) : null;
                return $timestamp ?: 0;
            })
            ->values();
    }

    /**
     * Enriquece las filas de órdenes con datos de clientes desde la tabla
     * «users» del sistema legacy y, posteriormente, desde el auth-service.
     * Si una fila ya tiene nombre, email o teléfono, no los sobreescribe.
     * @see self::hydrateOrdersWithAuthProfiles()
     */
    private function enrichAdminOrdersWithCustomerData(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        // Si no hay filas, retornamos temprano
        if ($rows->isEmpty()) {
            return $rows;
        }

        // Extraemos IDs de usuario únicos de las órdenes
        $userIds = $rows
            ->map(static fn ($row) => trim((string) ($row->user_id ?? '')))
            ->filter(static fn ($userId) => $userId !== '')
            ->unique()
            ->values();

        // Si no hay usuarios asociados, retornamos sin enriquecer
        if ($userIds->isEmpty()) {
            return $rows;
        }

        $usersById = collect();

        // Consultamos la tabla users del sistema legacy
        try {
            if (Schema::connection(self::LEGACY_CONNECTION)->hasTable('users')) {
                $usersById = DB::connection(self::LEGACY_CONNECTION)
                    ->table('users')
                    ->select('id', 'name', 'email', 'phone')
                    ->whereIn('id', $userIds->all())
                    ->get()
                    ->keyBy(static fn ($user) => (string) $user->id);
            }
        } catch (Throwable) {
            // Si falla la consulta legacy, continuamos sin esos datos
            $usersById = collect();
        }

        // Solo completamos campos que estén vacíos en la orden
        $enrichedRows = $rows->map(function ($row) use ($usersById) {
            $user = $usersById->get((string) ($row->user_id ?? ''));

            if (!$user) {
                return $row;
            }

            $customerName = trim((string) ($row->user_name ?? $row->customer_name ?? $row->billing_name ?? ''));
            $customerEmail = trim((string) ($row->user_email ?? $row->customer_email ?? $row->billing_email ?? ''));
            $customerPhone = trim((string) ($row->user_phone ?? $row->customer_phone ?? $row->billing_phone ?? $row->phone ?? ''));

            // Solo completamos campos vacíos para no pisar datos ya presentes
            if ($customerName === '') {
                $row->user_name = $user->name ?? null;
            }

            if ($customerEmail === '') {
                $row->user_email = $user->email ?? null;
            }

            if ($customerPhone === '') {
                $row->user_phone = $user->phone ?? null;
            }

            return $row;
        })->values();

        // Complementamos con perfiles del auth-service
        return $this->hydrateOrdersWithAuthProfiles($enrichedRows);
    }

    /**
     * Resuelve una clave única de fusión para una orden.
     * Prioriza el número de orden; si no existe, usa source + id.
     * Esta clave permite identificar la misma orden en diferentes bases de datos.
     */
    private function resolveOrderMergeKey(object $row): string
    {
        // Si la orden tiene número, lo usamos como clave principal
        $orderNumber = strtolower(trim((string) ($row->order_number ?? '')));

        if ($orderNumber !== '') {
            return 'order_number:' . $orderNumber;
        }

        // Sin número de orden, concatenamos fuente + ID
        $source = strtolower((string) ($row->order_source ?? 'microservice'));
        return 'source:' . $source . ':id:' . (string) ($row->id ?? '');
    }

    /**
     * Resuelve una clave única de fusión para un item de orden.
     * Combina la clave de la orden padre con producto, variante, precio, cantidad y total,
     * permitiendo identificar unívocamente cada línea de item entre diferentes fuentes.
     * @see self::resolveOrderMergeKey()
     */
    private function resolveOrderItemMergeKey(object $row): string
    {
        $orderMergeKey = trim((string) ($row->order_merge_key ?? ''));

        // Si no tiene clave de orden padre, la resolvemos
        if ($orderMergeKey === '') {
            $orderMergeKey = $this->resolveOrderMergeKey((object) [
                'order_number' => $row->order_number ?? null,
                'id' => $row->order_id ?? null,
                'order_source' => $row->order_source ?? null,
            ]);
        }

        // Normalizamos el nombre de variante
        $variantName = Str::of((string) ($row->variant_name ?? ''))->trim()->lower()->value();

        // Clave compuesta: orden + producto + variante + precio + cantidad + total
        return implode('|', [
            $orderMergeKey,
            'product:' . (string) ($row->product_id ?? ''),
            'variant:' . $variantName,
            'price:' . number_format((float) ($row->price ?? 0), 2, '.', ''),
            'qty:' . (int) ($row->quantity ?? 0),
            'total:' . number_format((float) ($row->total ?? 0), 2, '.', ''),
        ]);
    }

    /**
     * Calcula un puntaje de calidad de información para una fila de orden.
     * A mayor puntaje, más datos completos tiene la fila (nombre, email, teléfono,
     * factura, etc.). Se usa para elegir la mejor fila entre duplicados.
     */
    private function orderRowInformationScore(object $row): int
    {
        $name = trim((string) ($row->user_name ?? $row->customer_name ?? $row->billing_name ?? ''));
        $email = trim((string) ($row->user_email ?? $row->customer_email ?? $row->billing_email ?? ''));
        $phone = trim((string) ($row->user_phone ?? $row->customer_phone ?? $row->billing_phone ?? $row->phone ?? ''));
        $userId = trim((string) ($row->user_id ?? ''));
        $invoiceNumber = trim((string) ($row->invoice_number ?? ''));
        $invoiceDate = trim((string) ($row->invoice_date ?? ''));

        $score = 0;

        // Ponderación: userId (2), nombre real (4), email válido (5), teléfono (1), factura (3), microservicio (1)
        if ($userId !== '') {
            $score += 2;  // Tiene identificador de usuario
        }

        // Nombres placeholder no suman puntos
        if ($name !== '' && !in_array(Str::lower($name), ['cliente', 'sin nombre'], true)) {
            $score += 4;  // Tiene nombre real de cliente
        }

        // Email válido tiene el peso más alto
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $score += 5;  // Tiene email válido
        }

        if ($phone !== '') {
            $score += 1;  // Tiene teléfono
        }

        if ($invoiceNumber !== '' || $invoiceDate !== '') {
            $score += 3;  // Tiene datos de factura
        }

        // Las filas del microservicio suelen tener más datos estructurados
        if (strtolower((string) ($row->order_source ?? 'microservice')) === 'microservice') {
            $score += 1;
        }

        return $score;
    }

    /**
     * Calcula un puntaje de calidad de información para un item de orden.
     * Similar a orderRowInformationScore() pero para líneas de producto.
     * @see self::orderRowInformationScore()
     */
    private function orderItemInformationScore(object $row): int
    {
        $productId = (int) ($row->product_id ?? 0);
        $productName = trim((string) ($row->product_name ?? $row->name ?? ''));
        $variantName = trim((string) ($row->variant_name ?? ''));
        $total = (float) ($row->total ?? 0);

        $score = 0;

        // Ponderación: productId (2), nombre real (4), variante (1), total > 0 (2), microservicio (1)
        if ($productId > 0) {
            $score += 2;  // Tiene ID de producto
        }

        // Nombres autogenerados tipo «Producto #N» no suman puntos
        if ($productName !== '' && !Str::startsWith(Str::lower($productName), 'producto #')) {
            $score += 4;  // Tiene nombre real de producto
        }

        if ($variantName !== '') {
            $score += 1;  // Tiene variante
        }

        if ($total > 0) {
            $score += 2;  // Tiene total calculado
        }

        if (strtolower((string) ($row->order_source ?? 'microservice')) === 'microservice') {
            $score += 1;
        }

        return $score;
    }

    /**
     * Enriquece las órdenes con perfiles de clientes obtenidos desde el auth-service.
     * Primero intenta con el endpoint interno, y si faltan perfiles, usa el endpoint admin.
     * Solo completa campos vacíos (nombre, email, teléfono).
     * @see self::fetchAuthProfilesByUserIds()
     */
    private function hydrateOrdersWithAuthProfiles(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        // Si no hay filas, retornamos temprano
        if ($rows->isEmpty()) {
            return $rows;
        }

        $userIds = $rows
            ->map(static fn ($row) => trim((string) ($row->user_id ?? '')))
            ->filter(static fn ($userId) => $userId !== '')
            ->unique()
            ->values()
            ->all();

        // Si no hay IDs de usuario, no podemos enriquecer
        if ($userIds === []) {
            return $rows;
        }

        $profilesById = $this->fetchAuthProfilesByUserIds($userIds);
        if ($profilesById === []) {
            return $rows;
        }

        // Solo completamos campos vacíos con datos del perfil
        return $rows->map(static function ($row) use ($profilesById) {
            $userId = trim((string) ($row->user_id ?? ''));
            if ($userId === '' || !array_key_exists($userId, $profilesById)) {
                return $row;
            }

            $profile = $profilesById[$userId];
            $currentName = trim((string) ($row->user_name ?? $row->customer_name ?? $row->billing_name ?? ''));
            $currentEmail = trim((string) ($row->user_email ?? $row->customer_email ?? $row->billing_email ?? ''));
            $currentPhone = trim((string) ($row->user_phone ?? $row->customer_phone ?? $row->billing_phone ?? $row->phone ?? ''));

            // No sobreescribimos datos ya presentes en la orden
            if ($currentName === '' && !empty($profile['name'])) {
                $row->user_name = $profile['name'];
            }

            if ($currentEmail === '' && !empty($profile['email'])) {
                $row->user_email = $profile['email'];
            }

            if ($currentPhone === '' && !empty($profile['phone'])) {
                $row->user_phone = $profile['phone'];
            }

            return $row;
        })->values();
    }

    /**
     * Obtiene perfiles de clientes desde el auth-service.
     * Primero consulta el endpoint interno (con token interno) y, si faltan perfiles,
     * complementa con el endpoint admin (con token bearer de la solicitud actual).
     * @see self::fetchInternalAuthProfilesByUserIds()
     * @see self::fetchAdminAuthProfilesByUserIds()
     */
    private function fetchAuthProfilesByUserIds(array $userIds): array
    {
        // En entorno de testing o sin IDs, retornamos vacío
        if ($userIds === [] || app()->environment('testing')) {
            return [];
        }

        // Primero intentamos con el endpoint interno (requiere token interno)
        $profilesById = $this->fetchInternalAuthProfilesByUserIds($userIds);
        $missingUserIds = array_values(array_diff($userIds, array_keys($profilesById)));

        // Los usuarios faltantes los buscamos vía endpoint admin
        if ($missingUserIds !== []) {
            foreach ($this->fetchAdminAuthProfilesByUserIds($missingUserIds) as $userId => $profile) {
                $profilesById[$userId] = $profile;
            }
        }

        return $profilesById;
    }

    /**
     * Consulta perfiles de clientes usando el endpoint interno del auth-service.
     * Este endpoint usa un token interno (X-Internal-Token) configurado en services.auth.internal_token.
     * Si el endpoint no está disponible, retorna un arreglo vacío.
     * @see self::resolveAuthProfilesEndpoint()
     */
    private function fetchInternalAuthProfilesByUserIds(array $userIds): array
    {
        $endpoint = $this->resolveAuthProfilesEndpoint();
        // Si no hay endpoint configurado, salimos
        if ($endpoint === null) {
            return [];
        }

        try {
            $request = Http::acceptJson()->timeout(4);
            $token = trim((string) config('services.auth.internal_token', ''));

            // Si hay token interno, lo agregamos como cabecera
            if ($token !== '') {
                $request = $request->withHeaders(['X-Internal-Token' => $token]);
            }

            $response = $request->get($endpoint, [
                'ids' => implode(',', $userIds),
            ]);

            // Si la respuesta no es exitosa, retornamos vacío
            if (!$response->successful()) {
                return [];
            }

            $profiles = $response->json('data');
            if (!is_array($profiles)) {
                return [];
            }

            // Indexamos los perfiles por ID
            $indexedProfiles = [];
            foreach ($profiles as $profile) {
                if (!is_array($profile)) {
                    continue;
                }

                $id = trim((string) ($profile['id'] ?? ''));
                if ($id === '') {
                    continue;  // Saltamos perfiles sin ID
                }

                $indexedProfiles[$id] = [
                    'name' => trim((string) ($profile['name'] ?? '')),
                    'email' => trim((string) ($profile['email'] ?? '')),
                    'phone' => trim((string) ($profile['phone'] ?? '')),
                ];
            }

            return $indexedProfiles;
        } catch (Throwable $exception) {
            Log::warning('No se pudieron consultar perfiles de clientes en auth-service para órdenes admin.', [
                'error' => $exception->getMessage(),
                'users_count' => count($userIds),
            ]);

            return [];
        }
    }

    /**
     * Consulta perfiles de clientes usando el endpoint admin del auth-service.
     * Este endpoint requiere el token bearer de la solicitud actual del admin.
     * Si el token o endpoint no están disponibles, retorna un arreglo vacío.
     * @see self::resolveAuthAdminCustomersEndpoint()
     */
    private function fetchAdminAuthProfilesByUserIds(array $userIds): array
    {
        $token = trim((string) request()->bearerToken());
        $endpoint = $this->resolveAuthAdminCustomersEndpoint();

        // Necesitamos token bearer y endpoint para hacer la consulta
        if ($token === '' || $endpoint === null) {
            return [];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(4)
                ->get($endpoint, [
                    'ids' => implode(',', $userIds),
                ]);

            if (!$response->successful()) {
                return [];
            }

            $profiles = $response->json('data');
            if (!is_array($profiles)) {
                return [];
            }

            $indexedProfiles = [];
            foreach ($profiles as $profile) {
                if (!is_array($profile)) {
                    continue;
                }

                $id = trim((string) ($profile['id'] ?? ''));
                if ($id === '') {
                    continue;
                }

                $indexedProfiles[$id] = [
                    'name' => trim((string) ($profile['name'] ?? '')),
                    'email' => trim((string) ($profile['email'] ?? '')),
                    'phone' => trim((string) ($profile['phone'] ?? '')),
                ];
            }

            return $indexedProfiles;
        } catch (Throwable $exception) {
            Log::warning('No se pudieron consultar perfiles de clientes vía endpoint admin de auth-service.', [
                'error' => $exception->getMessage(),
                'users_count' => count($userIds),
            ]);

            return [];
        }
    }

    /**
     * Resuelve la URL del endpoint interno de perfiles en el auth-service.
     * Detecta si la URL base ya incluye /api o no, para construir la ruta correcta.
     * Ejemplo: «http://auth-service:8000/api/internal/users/profiles»
     */
    private function resolveAuthProfilesEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.auth.base_url', 'http://auth-service:8000/api'));
        // Sin URL base configurada, no podemos resolver el endpoint
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        // Si la base ya termina en /api, no duplicamos el segmento
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/internal/users/profiles';
        }

        return $baseUrl . '/api/internal/users/profiles';
    }

    /**
     * Resuelve la URL del endpoint admin de clientes en el auth-service.
     * Similar a resolveAuthProfilesEndpoint() pero para la ruta admin.
     * Ejemplo: «http://auth-service:8000/api/admin/customers»
     * @see self::resolveAuthProfilesEndpoint()
     */
    private function resolveAuthAdminCustomersEndpoint(): ?string
    {
        $baseUrl = trim((string) config('services.auth.base_url', 'http://auth-service:8000/api'));
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        // Construimos la ruta, evitando duplicar /api
        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/admin/customers';
        }

        return $baseUrl . '/api/admin/customers';
    }

    /**
     * Normaliza un estado de orden a un formato estándar (minúsculas, sin guiones).
     * Si el estado está vacío o es nulo, retorna «sin_estado».
     */
    private function normalizeReportStatus(mixed $status): string
    {
        $normalizedStatus = Str::of((string) $status)->trim()->lower()->replace('-', '_')->value();

        return $normalizedStatus !== '' ? $normalizedStatus : 'sin_estado';
    }

    /**
     * Extrae la clave de fecha (YYYY-MM-DD) a partir del campo created_at de una orden.
     * Se usa para agrupar ventas por día en los reportes.
     */
    private function reportDateKey(object $row): ?string
    {
        try {
            $createdAt = $row->created_at ?? null;
            return $createdAt ? Carbon::parse($createdAt)->toDateString() : null;
        } catch (Throwable) {
            // Si la fecha no es válida, retornamos null
            return null;
        }
    }

    /**
     * Obtiene el valor total de una orden.
     * @see self::orderNumericValue()
     */
    private function orderTotalValue(object $row): float
    {
        return $this->orderNumericValue($row, ['total'], 0.0);
    }

    /**
     * Obtiene el subtotal de una orden.
     * Si no hay subtotal explícito, usa el total como fallback.
     * @see self::orderNumericValue()
     */
    private function orderSubtotalValue(object $row): float
    {
        return $this->orderNumericValue($row, ['subtotal'], $this->orderTotalValue($row));
    }

    /**
     * Obtiene el costo de envío de una orden.
     * @see self::orderNumericValue()
     */
    private function orderShippingValue(object $row): float
    {
        return $this->orderNumericValue($row, ['shipping_cost', 'shipping'], 0.0);
    }

    /**
     * Obtiene el descuento aplicado a una orden.
     * @see self::orderNumericValue()
     */
    private function orderDiscountValue(object $row): float
    {
        return $this->orderNumericValue($row, ['discount_amount', 'discount'], 0.0);
    }

    /**
     * Extrae un valor numérico de un objeto probando múltiples nombres de propiedad candidatos.
     * Útil cuando diferentes fuentes (microservicio vs. legacy) usan nombres de columna distintos.
     */
    private function orderNumericValue(object $row, array $candidates, float $fallback = 0.0): float
    {
        // Recorremos los candidatos y retornamos el primer valor no vacío
        foreach ($candidates as $candidate) {
            if (!property_exists($row, $candidate)) {
                continue;  // La propiedad no existe en el objeto
            }

            $value = $row->{$candidate};
            if ($value === null || $value === '') {
                continue;  // La propiedad existe pero está vacía
            }

            return (float) $value;
        }

        return $fallback;
    }

    /**
     * Compara dos textos normalizados para evitar cambios falsos por mayúsculas o espacios.
     */
    private function sameNormalizedValue(?string $left, ?string $right): bool
    {
        return Str::lower(trim((string) ($left ?? ''))) === Str::lower(trim((string) ($right ?? '')));
    }

    /**
     * Normaliza un valor mixto a string nulleable para payloads e historial.
     */
    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }

    /**
     * Construye la consulta base de órdenes aplicando filtros administrativos y nombres de columnas por conexión.
     */
    private function buildOrdersQuery(?string $connection, Request $request)
    {
        $dbConnection = $connection ?: config('database.default');
        // Resolvemos nombres de columna según el esquema de la conexión actual
        $query = $this->query($connection)->table('orders');
        $statusCol = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';
        $paymentStatusCol = $this->firstExistingColumn('orders', ['payment_status'], $connection);
        $customerNameCol = $this->firstExistingColumn('orders', ['user_name', 'customer_name', 'billing_name'], $connection);
        $customerEmailCol = $this->firstExistingColumn('orders', ['user_email', 'customer_email', 'billing_email'], $connection);
        $likeOperator = $this->likeOperator($connection);

        $query->select('orders.*');

        // Compatibilidad: si la tabla usa «order_status» en lugar de «status», lo renombramos
        if (!Schema::connection($dbConnection)->hasColumn('orders', 'status') && Schema::connection($dbConnection)->hasColumn('orders', 'order_status')) {
            $query->addSelect('orders.order_status as status');
        }

        // Renombramos columnas de cliente si difieren del estándar «user_name» / «user_email»
        if ($customerNameCol && $customerNameCol !== 'user_name') {
            $query->addSelect("orders.{$customerNameCol} as user_name");
        }

        if ($customerEmailCol && $customerEmailCol !== 'user_email') {
            $query->addSelect("orders.{$customerEmailCol} as user_email");
        }

        // Subconsulta: conteo de items por orden
        $query->selectSub(function ($subQuery): void {
            $subQuery
                ->from('order_items')
                ->selectRaw('COUNT(*)')
                ->whereColumn('order_items.order_id', 'orders.id');
        }, 'items_count');

        // Filtro por rango de fechas
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->string('from_date')->toString());
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->string('to_date')->toString() . ' 23:59:59');
        }

        // Filtro por estado (soporta grupos de variantes)
        if ($request->filled('status')) {
            $statusValues = $this->expandAdminStatusFilterValues($request->string('status')->toString());

            if (count($statusValues) <= 1) {
                $query->where($statusCol, $statusValues[0] ?? $request->string('status')->toString());
            } else {
                $query->whereIn($statusCol, $statusValues);
            }
        }

        // Filtro por estado de pago
        if ($paymentStatusCol && $request->filled('payment_status')) {
            $query->where($paymentStatusCol, $request->string('payment_status')->toString());
        }

        // Búsqueda textual por número de orden, nombre o email del cliente
        if ($request->filled('search')) {
            $term = $request->string('search')->toString();
            $query->where(function ($searchQuery) use ($term, $customerNameCol, $customerEmailCol, $likeOperator) {
                $searchQuery->where('orders.order_number', $likeOperator, "%{$term}%");

                if ($customerNameCol) {
                    $searchQuery->orWhere("orders.{$customerNameCol}", $likeOperator, "%{$term}%");
                }

                if ($customerEmailCol) {
                    $searchQuery->orWhere("orders.{$customerEmailCol}", $likeOperator, "%{$term}%");
                }
            });
        }

        return $query;
    }

    /**
     * Retorna una instancia de conexión a base de datos.
     * Si se especifica una conexión, la usa; si no, usa la default de Laravel.
     */
    private function query(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }
}
