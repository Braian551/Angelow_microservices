<?php

// Comentario de mantenimiento: Esta migración describe la estructura persistente necesaria para el dominio.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea o ajusta tablas requeridas por el servicio.
     */
    public function up(): void
    {
        // Crea la tabla discount_types con los campos necesarios para este flujo del dominio.
        Schema::create('discount_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla discount_codes con los campos necesarios para este flujo del dominio.

        Schema::create('discount_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 20)->unique();
            $table->unsignedInteger('discount_type_id');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_single_use')->default(false);
            $table->string('created_by', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla discount_code_products con los campos necesarios para este flujo del dominio.

        Schema::create('discount_code_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('discount_code_id');
            $table->unsignedInteger('product_id');
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla discount_code_usage con los campos necesarios para este flujo del dominio.

        Schema::create('discount_code_usage', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('discount_code_id');
            $table->string('user_id', 20)->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->timestamp('used_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla percentage_discounts con los campos necesarios para este flujo del dominio.

        Schema::create('percentage_discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('discount_code_id');
            $table->decimal('percentage', 5, 2);
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->char('trial554', 1)->nullable();
        });

        // Crea la tabla fixed_amount_discounts con los campos necesarios para este flujo del dominio.

        Schema::create('fixed_amount_discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('discount_code_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla free_shipping_discounts con los campos necesarios para este flujo del dominio.

        Schema::create('free_shipping_discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('discount_code_id');
            $table->unsignedInteger('shipping_method_id')->nullable();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla bulk_discount_rules con los campos necesarios para este flujo del dominio.

        Schema::create('bulk_discount_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable();
            $table->decimal('discount_percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial548', 1)->nullable();
        });

        // Crea la tabla user_applied_discounts con los campos necesarios para este flujo del dominio.

        Schema::create('user_applied_discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->unsignedInteger('discount_code_id');
            $table->string('discount_code', 20);
            $table->decimal('discount_amount', 10, 2);
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->char('trial558', 1)->nullable();

            $table->index('user_id');
            $table->index('discount_code_id');
        });
    }

    /**
     * Revierte las tablas creadas por esta migración en orden seguro.
     */

    public function down(): void
    {
        // Revierte la tabla user_applied_discounts al deshacer la migración.
        Schema::dropIfExists('user_applied_discounts');
        // Revierte la tabla bulk_discount_rules al deshacer la migración.
        Schema::dropIfExists('bulk_discount_rules');
        // Revierte la tabla free_shipping_discounts al deshacer la migración.
        Schema::dropIfExists('free_shipping_discounts');
        // Revierte la tabla fixed_amount_discounts al deshacer la migración.
        Schema::dropIfExists('fixed_amount_discounts');
        // Revierte la tabla percentage_discounts al deshacer la migración.
        Schema::dropIfExists('percentage_discounts');
        // Revierte la tabla discount_code_usage al deshacer la migración.
        Schema::dropIfExists('discount_code_usage');
        // Revierte la tabla discount_code_products al deshacer la migración.
        Schema::dropIfExists('discount_code_products');
        // Revierte la tabla discount_codes al deshacer la migración.
        Schema::dropIfExists('discount_codes');
        // Revierte la tabla discount_types al deshacer la migración.
        Schema::dropIfExists('discount_types');
    }
};
