<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea solicitudes de reembolso separadas del estado final de la orden.
     */
    public function up(): void
    {
        Schema::create('order_refund_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('user_id', 40)->nullable();
            $table->string('user_email', 255)->nullable();
            $table->string('reason', 80);
            $table->text('details')->nullable();
            $table->string('evidence_path', 500)->nullable();
            $table->string('evidence_original_name', 255)->nullable();
            $table->string('status', 24)->default('requested');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    /**
     * Elimina las solicitudes de reembolso al revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_refund_requests');
    }
};
