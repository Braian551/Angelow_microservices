<?php

/*
|--------------------------------------------------------------------------
| Migración: tabla de caché y bloqueos de caché (auth-service)
|--------------------------------------------------------------------------
|
| Crea las tablas 'cache' y 'cache_locks' usadas por Laravel cuando
| el driver de caché configurado es 'database'. La tabla 'cache'
| almacena pares clave-valor con expiración, y 'cache_locks' gestiona
| bloqueos atómicos entre procesos para evitar condiciones de carrera.
|
| El auth-service puede usar Redis como driver de caché en producción,
| pero esta migración permite el fallback a base de datos cuando sea
| necesario (por ejemplo, en entornos sin Redis disponible).
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
