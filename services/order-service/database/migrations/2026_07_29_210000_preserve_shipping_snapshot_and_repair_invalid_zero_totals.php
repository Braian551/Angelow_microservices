<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('orders', 'shipping_method_name')) {
                $table->string('shipping_method_name', 100)->nullable();
            }
            if (!Schema::hasColumn('orders', 'shipping_delivery_time')) {
                $table->string('shipping_delivery_time', 80)->nullable();
            }
        });

        DB::table('orders')
            ->where('shipping_method_id', '<', 1)
            ->whereNotNull('shipping_address')
            ->update([
                'shipping_method_id' => null,
                'shipping_method_name' => 'Envío estándar',
                'shipping_delivery_time' => 'Coordinaremos el despacho contigo',
            ]);

        DB::table('orders')
            ->where('total', '<=', 0)
            ->orderBy('id')
            ->get()
            ->each(function (object $order): void {
                $subtotal = round((float) ($order->subtotal ?? 0), 2);
                $shippingCost = round((float) ($order->shipping_cost ?? 0), 2);
                $discountAmount = round((float) ($order->discount_amount ?? 0), 2);
                $grossAmount = round($subtotal + $shippingCost, 2);

                if ($grossAmount <= 0 || $discountAmount < $grossAmount) {
                    return;
                }

                $itemsSubtotal = round((float) DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->get(['price', 'quantity'])
                    ->sum(static fn (object $item): float => (float) $item->price * (int) $item->quantity), 2);

                if ($itemsSubtotal <= 0 || abs($itemsSubtotal - $subtotal) > 0.01) {
                    return;
                }

                DB::table('orders')->where('id', $order->id)->update([
                    'discount_amount' => 0,
                    'total' => $grossAmount,
                    'updated_at' => now(),
                ]);

                if (Schema::hasTable('order_status_history')) {
                    DB::table('order_status_history')->insert([
                        'order_id' => $order->id,
                        'change_type' => 'amount_correction',
                        'field_changed' => 'total',
                        'old_value' => (string) ($order->total ?? 0),
                        'new_value' => (string) $grossAmount,
                        'description' => 'Corrección automática de un descuento mayor o igual al valor del pedido.',
                        'created_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('orders', 'shipping_method_name') ? 'shipping_method_name' : null,
                Schema::hasColumn('orders', 'shipping_delivery_time') ? 'shipping_delivery_time' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
