<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el control agregado de intentos fallidos por credencial e IP.
     */
    public function up(): void
    {
        Schema::create('auth_login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('credential', 150);
            $table->string('ip_address', 45);
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->timestamps();

            $table->unique(['credential', 'ip_address'], 'auth_login_attempts_credential_ip_unique');
            $table->index('blocked_until');
        });
    }

    /**
     * Elimina la tabla agregada si se revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_login_attempts');
    }
};
