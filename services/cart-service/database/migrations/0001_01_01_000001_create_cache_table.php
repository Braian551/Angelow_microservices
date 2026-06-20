<?php

/*
|--------------------------------------------------------------------------
| Migración: tabla de caché y bloqueos de caché (cart-service)
|--------------------------------------------------------------------------
|
| Crea las tablas 'cache' y 'cache_locks' usadas por Laravel cuando
| el driver de caché configurado es 'database'. El cart-service usa
| Redis como driver principal para almacenar stock en tiempo real
| y rate-limits de carritos abandonados.
|
| Esta migración permite el fallback a base de datos en entornos
| donde Redis no esté disponible.
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
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration')->index();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
