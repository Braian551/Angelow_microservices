<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la política opcional de reembolso al producto para que órdenes pueda calcular vigencia.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_refundable')) {
                $table->boolean('is_refundable')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('products', 'refund_days')) {
                $table->unsignedSmallInteger('refund_days')->nullable()->after('is_refundable');
            }
        });
    }

    /**
     * Revierte los campos de política de reembolso si se deshace la migración.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'refund_days')) {
                $table->dropColumn('refund_days');
            }

            if (Schema::hasColumn('products', 'is_refundable')) {
                $table->dropColumn('is_refundable');
            }
        });
    }
};
