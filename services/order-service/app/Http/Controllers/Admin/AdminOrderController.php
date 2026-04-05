<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $connection = null;

        $query = $this->buildOrdersQuery($connection, $request);

        if ((clone $query)->count() === 0) {
            $connection = self::LEGACY_CONNECTION;
            $query = $this->buildOrdersQuery($connection, $request);
        }

        $dbConnection = $connection ?: config('database.default');

        $statusCol = $this->firstExistingColumn('orders', ['status', 'order_status'], $connection) ?: 'status';
        $customerNameCol = $this->firstExistingColumn('orders', ['user_name', 'customer_name', 'billing_name'], $connection);
        $customerEmailCol = $this->firstExistingColumn('orders', ['user_email', 'customer_email', 'billing_email'], $connection);

        $rows = $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $totalOrders = (clone $query)->count();
        $totalRevenue = (clone $query)->sum('total');
        $pendingOrders = (clone $query)->where($statusCol, 'pending')->count();
        $completedOrders = (clone $query)->whereIn($statusCol, ['delivered', 'completed'])->count();

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
                'stats' => [
                    'total_orders' => $totalOrders,
                    'total_revenue' => round((float) $totalRevenue, 2),
                    'pending_orders' => $pendingOrders,
                    'completed_orders' => $completedOrders,
                    'customer_name_column' => $customerNameCol,
                    'customer_email_column' => $customerEmailCol,
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
        ]);

        $connection = null;
        $query = $this->buildOrdersQuery($connection, $filtersRequest);

        // Si no hay datos en la BD distribuida, usa fallback legacy para no romper reportes.
        if ((clone $query)->count() === 0) {
            $connection = self::LEGACY_CONNECTION;
            $query = $this->buildOrdersQuery($connection, $filtersRequest);
        }

        $dbConnection = $connection ?: config('database.default');
        $totalOrders   = (clone $query)->count();
        $totalRevenue  = (clone $query)->sum('total');
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        // Detectar columna de estado real
        $statusCol = 'status';
        if (!Schema::connection($dbConnection)->hasColumn('orders', 'status')
            && Schema::connection($dbConnection)->hasColumn('orders', 'order_status')) {
            $statusCol = 'order_status';
        }

        $byStatus = (clone $query)
            ->select($statusCol . ' as status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy($statusCol)
            ->get();

        // Ventas por dia (ultimos 30 dias por defecto)
        $dailySalesQuery = $this->query($connection)->table('orders');
        if ($from) {
            $dailySalesQuery->where('created_at', '>=', $from);
        } else {
            $dailySalesQuery->where('created_at', '>=', now()->subDays(30));
        }
        if ($to) {
            $dailySalesQuery->where('created_at', '<=', $to . ' 23:59:59');
        }

        $dailySales = $dailySalesQuery
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $rows = $dailySales->map(static function ($row) {
            return [
                'date' => $row->date,
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
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

        // Compatibilidad: se devuelven claves snake_case y camelCase.
        $payload = [
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'avg_order_value' => $avgOrderValue,
            'total_products' => 0,
            'by_status' => $byStatus,
            'daily_sales' => $dailySales,
            'by_payment_method' => $byPaymentMethod,
            'rows' => $rows,
            'totalOrders' => $totalOrders,
            'totalRevenue' => round($totalRevenue, 2),
            'avgOrderValue' => $avgOrderValue,
            'totalProducts' => 0,
        ];

        return response()->json([
            'success' => true,
            'data'    => $payload,
        ]);
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
