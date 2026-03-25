<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colombian_banks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bank_code', 10)->unique();
            $table->string('bank_name', 100);
            $table->boolean('is_active')->default(true);
            $table->char('trial551', 1)->nullable();
        });

        Schema::create('bank_account_config', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bank_code', 10);
            $table->string('account_number', 50);
            $table->string('account_type', 20);
            $table->string('account_holder', 100);
            $table->string('identification_type', 10)->default('cc');
            $table->string('identification_number', 20);
            $table->string('email', 100)->nullable();
            $table->string('phone', 15)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial548', 1)->nullable();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id')->nullable();
            $table->string('user_id', 20)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('reference_number', 50)->nullable();
            $table->string('payment_proof', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('verified_by', 20)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('order_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('bank_account_config');
        Schema::dropIfExists('colombian_banks');
    }
};
