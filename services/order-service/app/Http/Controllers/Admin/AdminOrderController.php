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

    /**
     * Ordenes recientes para el dashboard admin.
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->input('limit', 12), 50));
        $connection = null;

        $query = $this->buildOrdersQuery($connection, null, null);

        if ((clone $query)->count() === 0) {
            $connection = self::LEGACY_CONNECTION;
            $query = $this->buildOrdersQuery($connection, null, null);
        }

        $dbConnection = $connection ?: config('database.default');
        $columns = ['id', 'created_at', 'total'];

        if (Schema::connection($dbConnection)->hasColumn('orders', 'status')) {
            $columns[] = 'status';
        }
        if (Schema::connection($dbConnection)->hasColumn('orders', 'order_status')) {
            $columns[] = 'order_status';
        }
        if (Schema::connection($dbConnection)->hasColumn('orders', 'payment_status')) {
            $columns[] = 'payment_status';
        }
        if (Schema::connection($dbConnection)->hasColumn('orders', 'user_name')) {
            $columns[] = 'user_name';
        }
        if (Schema::connection($dbConnection)->hasColumn('orders', 'customer_name')) {
            $columns[] = 'customer_name';
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get($columns);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    /**
     * Reporte de ventas para el panel admin.
     */
    public function reportSales(Request $request): JsonResponse
    {
        $from = $request->input('from');
        $to   = $request->input('to');

        $connection = null;
        $query = $this->buildOrdersQuery($connection, $from, $to);

        // Si no hay datos en la BD distribuida, usa fallback legacy para no romper reportes.
        if ((clone $query)->count() === 0) {
            $connection = self::LEGACY_CONNECTION;
            $query = $this->buildOrdersQuery($connection, $from, $to);
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

    private function buildOrdersQuery(?string $connection, ?string $from, ?string $to)
    {
        $query = $this->query($connection)->table('orders');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        return $query;
    }

    private function query(?string $connection)
    {
        return $connection ? DB::connection($connection) : DB::connection();
    }
}
