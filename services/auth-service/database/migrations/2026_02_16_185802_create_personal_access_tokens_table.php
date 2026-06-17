<?php

/*
|--------------------------------------------------------------------------
| Migración: tabla personal_access_tokens (Sanctum)
|--------------------------------------------------------------------------
|
| Crea la tabla requerida por Laravel Sanctum para la autenticación
| basada en tokens de API. La columna tokenable_id se define como
| string para compatibilidad con IDs alfanuméricos del legacy (uniqid).
|
| La migración posterior 2026_03_30_230000 corrige el tipo de dato
| de tokenable_id según el motor de BD.
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            // Los IDs de usuarios legacy son string (uniqid), no bigint.
            $table->string('tokenable_type');
            $table->string('tokenable_id');
            $table->index(['tokenable_type', 'tokenable_id']);
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->char('trial554', 1)->nullable();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
