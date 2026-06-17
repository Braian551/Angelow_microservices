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
        if (Schema::hasTable('inventory_alerts')) {
            return;
        }

        // Crea la tabla inventory_alerts con los campos necesarios para este flujo del dominio.

        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('variant_id')->unique();
            $table->unsignedInteger('product_id')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('color_name', 120)->nullable();
            $table->string('size_label', 120)->nullable();
            $table->string('sku', 80)->nullable();
            $table->integer('stock')->default(0);
            $table->string('status', 20)->default('out');
            $table->timestamp('out_of_stock_since')->nullable();
            $table->timestamp('last_initial_notification_at')->nullable();
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'out_of_stock_since'], 'inventory_alerts_status_out_since_idx');
            $table->index('product_id');
        });
    }

    /**
     * Revierte las tablas creadas por esta migración en orden seguro.
     */

    public function down(): void
    {
        // Revierte la tabla inventory_alerts al deshacer la migración.
        Schema::dropIfExists('inventory_alerts');
    }
};