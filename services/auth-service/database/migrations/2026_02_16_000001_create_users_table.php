<?php

/*
|--------------------------------------------------------------------------
| Migración: tabla de usuarios
|--------------------------------------------------------------------------
|
| Crea la tabla `users` reflejando el esquema del sistema legacy Angelow.
| Usa VARCHAR(20) como llave primaria para compatibilidad con los IDs
| basados en uniqid() del monolito original.
|
| Columnas legacy:
|   - trial548: columna remanente de la base original, se mantiene
|     por compatibilidad durante la migración.
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla users.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 15)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->enum('role', ['customer', 'admin'])->default('customer');
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
            $table->datetime('last_access')->nullable();
            $table->string('remember_token', 255)->nullable();
            $table->datetime('token_expiry')->nullable();
            $table->char('trial548', 1)->nullable();

            // Índices para búsquedas frecuentes
            $table->index('email');
            $table->index('phone');
            $table->index('role');
        });
    }

    /**
     * Revierte la migración: elimina la tabla users.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
