<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminOrderController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    private function likeOperator(?string $connection = null): string
    {
        $driver = ($connection ? DB::connection($connection) : DB::connection())->getDriverName();
        return $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

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
     * Ordenes recientes para el dashboard admin.
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->input('limit', 12), 500));
        $statsLimit = max($limit, 1000);

        $distributedRows = $this->fetchAdminOrdersRows(null, $request, $statsLimit);
        $legacyRows = $this->fetchAdminOrdersRows(self::LEGACY_CONNECTION, $request, $statsLimit);
        $mergedRows = $this->enrichAdminOrdersWithCustomerData(
            $this->mergeAdminOrderRows($distributedRows, $legacyRows)
        );

        $rows = $mergedRows
            ->take($limit)
            ->values();

        $totalOrders = $mergedRows->count();
        $totalRevenue = $mergedRows->sum(static fn ($row) => (float) ($row->total ?? 0));
        $pendingOrders = $mergedRows->filter(static function ($row): bool {
            $status = strtolower((string) ($row->status ?? $row->order_status ?? 'pending'));
            return $status === 'pending';
        })->count();
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
     * Reporte de ventas para el panel admin.
     */
    public function reportSales(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');
        $filtersRequest = new Request([
            'from_date' => $from,
            'to_date' => $to,
            'status' => $request->input('status'),
        ]);

        $connection = null;
        $query = $this->buildAnalyticsOrdersQuery($connection, $filtersRequest);

        // Si no hay datos en la BD distribuida, usa fallback legacy para no romper reportes.
        if ((clone $query)->count() === 0) {
            $connection = self::LEGACY_CONNECTION;
            $query = $this->buildAnalyticsOrdersQuery($connection, $filtersRequest);
        }

        $dbConnection = $connection ?: config('database.default');
        $totalOrders   = (clone $query)->count();
        $totalRevenue  = (clone $query)->sum('total');
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;
        $subtotalColumn = $this->firstExistingColumn('orders', ['subtotal'], $connection);
        $shippingColumn = $this->firstExistingColumn('orders', ['shipping_cost', 'shipping'], $connection);
        $discountColumn = $this->firstExistingColumn('orders', ['discount_amount', 'discount'], $connection);
        $subtotalExpression = $subtotalColumn ? "COALESCE({$subtotalColumn}, total)" : 'COALESCE(total, 0)';
        $shippingExpression = $shippingColumn ? "COALESCE({$shippingColumn}, 0)" : '0';
        $discountExpression = $discountColumn ? "COALESCE({$discountColumn}, 0)" : '0';

        // Detectar columna de estado real
        $statusCol = 'status';
        if (!Schema::connection($dbConnection)->hasColumn('orders', 'status')
            && Schema::connection($dbConnection)->hasColumn('orders', 'order_status')) {
            $statusCol = 'order_status';
        }

        $byStatus = (clone $query)
            ->select(
                DB::raw($statusCol . ' as status'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue'),
            )
            ->groupBy($statusCol)
            ->get();

        $dailySales = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue'),
                DB::raw("SUM({$subtotalExpression}) as subtotal"),
                DB::raw("SUM({$shippingExpression}) as shipping"),
                DB::raw("SUM({$discountExpression}) as discount"),
                DB::raw('AVG(total) as avg_order_value'),
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $rows = $dailySales->map(static function ($row) {
            return [
                'date' => $row->date,
                'orders' => (int) ($row->orders ?? 0),
                'subtotal' => (float) ($row->subtotal ?? 0),
                'shipping' => (float) ($row->shipping ?? 0),
                'discount' => (float) ($row->discount ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
                'avg_order_value' => (float) ($row->avg_order_value ?? 0),
                'products' => 0,
            ];
        })->values();

        // Metodos de pago
        $byPaymentMethod = collect();
        if (Schema::connection($dbConnection)->hasColumn('orders', 'payment_method')) {
            $byPaymentMethod = (clone $query)
                ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
                ->whereNotNull('payment_method')
                ->groupBy('payment_method')
                ->get();
        }

        $totalShipping = (clone $query)->sum(DB::raw($shippingExpression));
        $totalDiscount = (clone $query)->sum(DB::raw($discountExpression));

        // Compatibilidad: se devuelven claves snake_case y camelCase.
        $payload = [
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'avg_order_value' => $avgOrderValue,
            'total_shipping' => round((float) $totalShipping, 2),
            'total_discount' => round((float) $totalDiscount, 2),
            'total_products' => 0,
            'by_status' => $byStatus,
            'daily_sales' => $dailySales,
            'by_payment_method' => $byPaymentMethod,
            'rows' => $rows,
            'totalOrders' => $totalOrders,
            'totalRevenue' => round($totalRevenue, 2),
            'avgOrderValue' => $avgOrderValue,
            'totalShipping' => round((float) $totalShipping, 2),
            'totalDiscount' => round((float) $totalDiscount, 2),
            'totalProducts' => 0,
        ];

        return response()->json([
            'success' => true,
            'data'    => $payload,
        ]);
    }

    /**
     * Reporte de productos populares basado en ordenes reales.
     */
    public function reportProducts(Request $request): JsonResponse
    {
        $limit = max(5, min((int) $request->input('limit', 50), 100));
        $filtersRequest = new Request([
            'from_date' => $request->input('from'),
            'to_date' => $request->input('to'),
        ]);

        $connection = null;
        $query = $this->buildAnalyticsOrderItemsQuery($connection, $filtersRequest);

        if ((clone $query)->count() === 0) {
            $connection = self::LEGACY_CONNECTION;
            $query = $this->buildAnalyticsOrderItemsQuery($connection, $filtersRequest);
        }

        $productNameColumn = $this->firstExistingColumn('order_items', ['product_name', 'name'], $connection) ?: 'product_name';

        $rows = (clone $query)
            ->select(
                'oi.product_id',
                DB::raw("COALESCE(MAX(oi.{$productNameColumn}), CONCAT('Producto #', oi.product_id)) as name"),
                DB::raw('COUNT(DISTINCT oi.order_id) as times_sold'),
                DB::raw('SUM(oi.quantity) as total_quantity'),
                DB::raw('AVG(oi.price) as avg_price'),
                DB::raw('SUM(oi.total) as total_revenue'),
                DB::raw('MIN(o.created_at) as first_order_at'),
                DB::raw('MAX(o.created_at) as last_order_at'),
            )
            ->groupBy('oi.product_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(static function ($row) {
                return [
                    'id' => (int) ($row->product_id ?? 0),
                    'product_id' => (int) ($row->product_id ?? 0),
                    'name' => $row->name,
                    'times_sold' => (int) ($row->times_sold ?? 0),
                    'total_quantity' => (int) ($row->total_quantity ?? 0),
                    'units_sold' => (int) ($row->total_quantity ?? 0),
                    'avg_price' => round((float) ($row->avg_price ?? 0), 2),
                    'total_revenue' => round((float) ($row->total_revenue ?? 0), 2),
                    'revenue' => round((float) ($row->total_revenue ?? 0), 2),
                    'first_order_at' => $row->first_order_at,
                    'last_order_at' => $row->last_order_at,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * Reporte de clientes recurrentes basado en ordenes reales.
     */
    public function reportCustomers(Request $request): JsonResponse
    {
        $minOrders = max(1, min((int) $request->input('min_orders', 2), 50));
        $filtersRequest = new Request([
            'from_date' => $request->input('from'),
            'to_date' => $request->input('to'),
        ]);

        $connection = null;
        $query = $this->buildAnalyticsOrdersQuery($connection, $filtersRequest);

        if ((clone $query)->count() === 0) {
            $connection = self::LEGACY_CONNECTION;
            $query = $this->buildAnalyticsOrdersQuery($connection, $filtersRequest);
        }

        $orders = (clone $query)
            ->orderByDesc('created_at')
            ->get();

        $customers = [];

        foreach ($orders as $order) {
            $userId = trim((string) ($order->user_id ?? ''));
            $name = trim((string) ($order->user_name ?? $order->customer_name ?? $order->billing_name ?? ''));
            $email = strtolower(trim((string) ($order->user_email ?? $order->customer_email ?? $order->billing_email ?? '')));
            $customerKey = $userId !== ''
                ? 'id:' . $userId
                : ($email !== '' ? 'email:' . $email : 'guest:' . (string) $order->id);

            if (!isset($customers[$customerKey])) {
                $customers[$customerKey] = [
                    'id' => $userId !== '' ? $userId : $customerKey,
                    'user_id' => $userId !== '' ? $userId : null,
                    'name' => $name !== '' ? $name : 'Cliente sin nombre',
                    'email' => $email !== '' ? $email : null,
                    'orders_count' => 0,
                    'total_spent' => 0.0,
                    'first_order' => null,
                    'last_order' => null,
                ];
            }

            $customers[$customerKey]['orders_count']++;
            $customers[$customerKey]['total_spent'] += (float) ($order->total ?? 0);

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

        $distribution = [
            ['segment' => '1 orden', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] === 1)->count()],
            ['segment' => '2-5 órdenes', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] >= 2 && (int) $row['orders_count'] <= 5)->count()],
            ['segment' => '6-10 órdenes', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] >= 6 && (int) $row['orders_count'] <= 10)->count()],
            ['segment' => 'Más de 10 órdenes', 'customer_count' => $rows->filter(fn ($row) => (int) $row['orders_count'] > 10)->count()],
        ];

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

    private function buildAnalyticsOrdersQuery(?string $connection, Request $request)
    {
        $query = $this->buildOrdersQuery($connection, $request);
        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';

        if (!$request->filled('status')) {
            $query->where($statusColumn, '!=', 'cancelled');
        }

        return $query;
    }

    private function buildAnalyticsOrderItemsQuery(?string $connection, Request $request)
    {
        $statusColumn = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';
        $query = $this->query($connection)
            ->table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id');

        if ($request->filled('from_date')) {
            $query->where('o.created_at', '>=', $request->string('from_date')->toString());
        }

        if ($request->filled('to_date')) {
            $query->where('o.created_at', '<=', $request->string('to_date')->toString() . ' 23:59:59');
        }

        if ($request->filled('status')) {
            $query->where('o.' . $statusColumn, $request->string('status')->toString());
        } else {
            $query->where('o.' . $statusColumn, '!=', 'cancelled');
        }

        return $query;
    }

    private function fetchAdminOrdersRows(?string $connection, Request $request, int $limit): \Illuminate\Support\Collection
    {
        try {
            $source = $connection === self::LEGACY_CONNECTION ? 'legacy' : 'microservice';

            return $this->buildOrdersQuery($connection, $request)
                ->orderByDesc('created_at')
                ->limit($limit)
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

    private function mergeAdminOrderRows(\Illuminate\Support\Collection $distributedRows, \Illuminate\Support\Collection $legacyRows): \Illuminate\Support\Collection
    {
        return $distributedRows
            ->concat($legacyRows)
            ->unique(function ($row): string {
                $orderNumber = strtolower(trim((string) ($row->order_number ?? '')));

                if ($orderNumber !== '') {
                    return 'order_number:' . $orderNumber;
                }

                $source = strtolower((string) ($row->order_source ?? 'microservice'));
                return 'source:' . $source . ':id:' . (string) ($row->id ?? '');
            })
            ->sortByDesc(static function ($row): int {
                $rawDate = $row->created_at ?? null;
                $timestamp = is_string($rawDate) ? strtotime($rawDate) : null;
                return $timestamp ?: 0;
            })
            ->values();
    }

    private function enrichAdminOrdersWithCustomerData(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $userIds = $rows
            ->map(static fn ($row) => trim((string) ($row->user_id ?? '')))
            ->filter(static fn ($userId) => $userId !== '')
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return $rows;
        }

        $usersById = collect();

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
            $usersById = collect();
        }

        return $rows->map(static function ($row) use ($usersById) {
            $user = $usersById->get((string) ($row->user_id ?? ''));

            if (!$user) {
                return $row;
            }

            $customerName = trim((string) ($row->user_name ?? $row->customer_name ?? $row->billing_name ?? ''));
            $customerEmail = trim((string) ($row->user_email ?? $row->customer_email ?? $row->billing_email ?? ''));
            $customerPhone = trim((string) ($row->user_phone ?? $row->customer_phone ?? $row->billing_phone ?? $row->phone ?? ''));

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
    }

    private function buildOrdersQuery(?string $connection, Request $request)
    {
        $dbConnection = $connection ?: config('database.default');
        $query = $this->query($connection)->table('orders');
        $statusCol = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';
        $paymentStatusCol = $this->firstExistingColumn('orders', ['payment_status'], $connection);
        $customerNameCol = $this->firstExistingColumn('orders', ['user_name', 'customer_name', 'billing_name'], $connection);
        $customerEmailCol = $this->firstExistingColumn('orders', ['user_email', 'customer_email', 'billing_email'], $connection);
        $likeOperator = $this->likeOperator($connection);

        $query->select('orders.*');

        if (!Schema::connection($dbConnection)->hasColumn('orders', 'status') && Schema::connection($dbConnection)->hasColumn('orders', 'order_status')) {
            $query->addSelect('orders.order_status as status');
        }

        if ($customerNameCol && $customerNameCol !== 'user_name') {
            $query->addSelect("orders.{$customerNameCol} as user_name");
        }

        if ($customerEmailCol && $customerEmailCol !== 'user_email') {
            $query->addSelect("orders.{$customerEmailCol} as user_email");
        }

        $query->selectSub(function ($subQuery): void {
            $subQuery
                ->from('order_items')
                ->selectRaw('COUNT(*)')
                ->whereColumn('order_items.order_id', 'orders.id');
        }, 'items_count');

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->string('from_date')->toString());
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->string('to_date')->toString() . ' 23:59:59');
        }

        if ($request->filled('status')) {
            $query->where($statusCol, $request->string('status')->toString());
        }

        if ($paymentStatusCol && $request->filled('payment_status')) {
            $query->where($paymentStatusCol, $request->string('payment_status')->toString());
        }

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

    private function query(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }
}
