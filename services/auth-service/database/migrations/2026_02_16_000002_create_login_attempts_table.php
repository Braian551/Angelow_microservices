<?php

/*
|--------------------------------------------------------------------------
| Migración: tabla de intentos de inicio de sesión
|--------------------------------------------------------------------------
|
| Registra intentos fallidos de login para protección contra
| ataques de fuerza bruta. Cada fila almacena el usuario
| intentado, dirección IP y fecha del intento.
|
| Los índices compuestos permiten consultas rápidas por
| usuario+fecha o IP+fecha para detectar patrones anómalos.
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla login_attempts.
     */
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 255);
            $table->string('ip_address', 45);
            $table->timestamp('attempt_date')->useCurrent();
            $table->char('trial551', 1)->nullable();

            $table->index(['username', 'attempt_date']);
            $table->index(['ip_address', 'attempt_date']);
        });
    }

    /**
     * Revierte la migración: elimina la tabla login_attempts.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
