<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de direcciones de usuario para shipping-service.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_addresses')) {
            return;
        }

        Schema::create('user_addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id', 20);
            $table->string('recipient_name', 100);
            $table->string('phone', 15)->nullable();
            $table->string('address_line_1', 180);
            $table->string('address_line_2', 180)->nullable();
            $table->string('city', 100);
            $table->string('department', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Colombia');
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};