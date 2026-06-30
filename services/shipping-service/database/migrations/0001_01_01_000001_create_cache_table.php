<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración estándar de Laravel para la tabla de caché.
 *
 * Crea dos tablas necesarias para el driver de caché 'database':
 * - cache: almacena pares clave/valor con tiempo de expiración.
 * - cache_locks: gestiona bloqueos distribuidos para operaciones
 *   que requieren exclusión mutua entre procesos.
 *
 * La tabla de caché es usada por el middleware EnsureAdmin para
 * almacenar temporalmente los datos de autenticación (5 minutos).
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración creando las tablas de caché.
     */
    public function up(): void
    {
        // Tabla principal de caché
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        // Tabla de bloqueos de caché (para operaciones atómicas)
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    /**
     * Revierte la migración eliminando las tablas de caché.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
