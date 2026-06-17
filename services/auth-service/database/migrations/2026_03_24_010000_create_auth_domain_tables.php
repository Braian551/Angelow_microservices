<?php

/*
|--------------------------------------------------------------------------
| Migración: tablas del dominio de autenticación
|--------------------------------------------------------------------------
|
| Crea las tablas adicionales del dominio auth:
|   - access_tokens: tokens de acceso legacy (anteriores a Sanctum)
|   - google_auth: vinculación de cuentas Google (Firebase)
|   - password_resets: códigos de recuperación de contraseña
|   - sessions: sesiones web por database driver
|
| Columnas legacy: trial548, trial551, trial554 (compatibilidad).
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea las tablas del dominio auth.
     */
    public function up(): void
    {
        /*
         * Tokens de acceso legacy (previo a Sanctum).
         * Almacena tokens emitidos antes de la migración a Sanctum
         * para mantener compatibilidad con sesiones activas.
         */
        Schema::create('access_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->string('token', 255);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->boolean('is_revoked')->default(false);
            $table->char('trial548', 1)->nullable();

            $table->index('user_id');
        });

        /*
         * Vinculación de cuentas de Google (Firebase Auth).
         * Almacena el google_id y access_token para cada usuario
         * que haya iniciado sesión con Google.
         */
        Schema::create('google_auth', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->string('google_id', 255);
            $table->string('access_token', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial551', 1)->nullable();

            $table->unique('google_id');
            $table->index('user_id');
        });

        /*
         * Códigos de recuperación de contraseña.
         * Cada fila representa un código generado, con su hash,
         * fecha de expiración y estado de uso.
         */
        Schema::create('password_resets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->string('token', 255);
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('user_id');
            $table->index('token');
        });

        /*
         * Sesiones web para el driver "database" de Laravel.
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->string('user_id', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
            $table->char('trial554', 1)->nullable();

            $table->index('user_id');
            $table->index('last_activity');
        });
    }

    /**
     * Revierte la migración: elimina las tablas del dominio auth.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('google_auth');
        Schema::dropIfExists('access_tokens');
    }
};
