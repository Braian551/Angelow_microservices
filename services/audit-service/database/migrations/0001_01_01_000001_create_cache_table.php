<?php

/*
|--------------------------------------------------------------------------
| Migración del sistema de caché de Laravel
|--------------------------------------------------------------------------
|
| Crea las tablas `cache` y `cache_locks` requeridas por los stores
| de caché con driver "database". El audit-service usa Redis como
| store principal, pero estas tablas se mantienen por compatibilidad
| con configuraciones alternativas de caché.
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea las tablas de caché.
     */
    public function up(): void
    {
        /*
         * Tabla principal de caché: clave única, valor serializado
         * y timestamp de expiración indexado para limpieza eficiente.
         */
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        /*
         * Tabla de locks de caché: evita condiciones de carrera
         * cuando múltiples procesos acceden a la misma clave.
         */
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    /**
     * Revierte la migración: elimina las tablas de caché.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
