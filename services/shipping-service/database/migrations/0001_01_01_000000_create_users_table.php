<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->string('delivery_time', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->text('available_cities')->nullable();
            $table->integer('estimated_days_min')->default(1);
            $table->integer('estimated_days_max')->default(3);
            $table->string('city', 100)->default('Medellin');
            $table->decimal('free_shipping_minimum', 10, 2)->nullable();
            $table->string('icon', 50)->default('fas fa-truck');
            $table->char('trial554', 1)->nullable();
        });

        Schema::create('shipping_price_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2)->nullable();
            $table->decimal('shipping_cost', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial554', 1)->nullable();
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->string('address_type', 20)->default('casa');
            $table->string('alias', 50);
            $table->string('recipient_name', 100);
            $table->string('recipient_phone', 15);
            $table->string('address', 255);
            $table->string('complement', 100)->nullable();
            $table->string('neighborhood', 100);
            $table->string('building_type', 20)->default('casa');
            $table->string('building_name', 100)->nullable();
            $table->string('apartment_number', 20)->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->decimal('gps_accuracy', 10, 2)->nullable();
            $table->timestamp('gps_timestamp')->nullable();
            $table->boolean('gps_used')->default(false);
            $table->char('trial558', 1)->nullable();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('shipping_price_rules');
        Schema::dropIfExists('shipping_methods');
    }
};
