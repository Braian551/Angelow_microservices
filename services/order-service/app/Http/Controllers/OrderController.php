<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class OrderController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

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

    public function show(int $id): JsonResponse
    {
        $order = $this->findOrderByConnection(null, $id);
        $sourceConnection = null;

        if (!$order) {
            $order = $this->findOrderByConnection(self::LEGACY_CONNECTION, $id);
            $sourceConnection = self::LEGACY_CONNECTION;
        }

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        $items = $this->fetchOrderItemsByConnection($sourceConnection, $id);
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
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer'],
            'items.*.color_variant_id' => ['nullable', 'integer'],
            'items.*.size_variant_id' => ['nullable', 'integer'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['required_with:items', 'numeric'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.total' => ['required_with:items', 'numeric'],
        ]);

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
                $orderPayload['discount_amount'] = 0;
            }

            $optionalOrderFields = [
                'shipping_cost' => $data['shipping_cost'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_status' => $data['payment_status'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_city' => $data['shipping_city'] ?? null,
                'shipping_method_id' => $data['shipping_method_id'] ?? null,
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

            $id = DB::table('orders')->insertGetId($orderPayload);

            if (!empty($data['items']) && Schema::hasTable('order_items')) {
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

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        return response()->json([
            'message' => 'Orden creada',
            'id' => $id,
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:20'],
            'changed_by' => ['nullable', 'string', 'max:20'],
            'changed_by_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $order = DB::table('orders')->where('id', $id)->first();

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        DB::table('orders')->where('id', $id)->update([
            'status' => $data['status'],
            'updated_at' => now(),
        ]);

        DB::table('order_status_history')->insert([
            'order_id' => $id,
            'changed_by' => $data['changed_by'] ?? null,
            'changed_by_name' => $data['changed_by_name'] ?? null,
            'change_type' => 'status_change',
            'field_changed' => 'status',
            'old_value' => $order->status,
            'new_value' => $data['status'],
            'description' => $data['description'] ?? 'Cambio de estado de la orden',
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Estado actualizado',
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
        try {
            return $this->query($connection)
                ->table('order_items')
                ->where('order_id', $orderId)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function fetchOrderHistoryByConnection(?string $connection, int $orderId): Collection
    {
        try {
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

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
