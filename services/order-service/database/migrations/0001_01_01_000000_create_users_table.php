<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_number', 20)->unique();
            $table->string('invoice_number', 20)->nullable();
            $table->string('user_id', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->unsignedInteger('shipping_method_id')->nullable();
            $table->unsignedInteger('shipping_address_id')->nullable();
            $table->text('billing_address')->nullable();
            $table->unsignedInteger('billing_address_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->string('invoice_resolution', 50)->nullable();
            $table->timestamp('invoice_date')->nullable();
            $table->char('trial554', 1)->nullable();

            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('color_variant_id')->nullable();
            $table->unsignedInteger('size_variant_id')->nullable();
            $table->string('product_name', 255);
            $table->string('variant_name', 255)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('total', 10, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial551', 1)->nullable();

            $table->index('order_id');
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('changed_by', 20)->nullable();
            $table->string('changed_by_name', 100)->nullable();
            $table->string('change_type', 20)->default('other');
            $table->string('field_changed', 100)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial551', 1)->nullable();

            $table->index('order_id');
        });

        Schema::create('order_views', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('user_id', 20);
            $table->timestamp('viewed_at')->useCurrent();
            $table->char('trial551', 1)->nullable();

            $table->unique(['order_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_views');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
