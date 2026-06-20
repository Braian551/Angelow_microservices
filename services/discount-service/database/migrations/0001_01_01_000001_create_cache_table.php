<?php

// Comentario de mantenimiento: Esta migración describe la estructura persistente necesaria para el dominio.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crea la tabla cache con los campos necesarios para este flujo del dominio.
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        // Crea la tabla cache_locks con los campos necesarios para este flujo del dominio.

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revierte la tabla cache al deshacer la migración.
        Schema::dropIfExists('cache');
        // Revierte la tabla cache_locks al deshacer la migración.
        Schema::dropIfExists('cache_locks');
    }
};
