<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración estándar de Laravel para la tabla de trabajos en cola (jobs).
 *
 * Crea tres tablas necesarias para el sistema de colas de Laravel:
 * - jobs: almacena los trabajos pendientes y en ejecución.
 * - job_batches: agrupa trabajos en lotes (batch processing).
 * - failed_jobs: registra trabajos fallidos para depuración.
 *
 * Estas tablas son usadas cuando el driver de cola está configurado
 * como 'database' (config/queue.php).
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración creando las tablas de colas.
     */
    public function up(): void
    {
        // Tabla principal de trabajos en cola
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Tabla de lotes de trabajos
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // Tabla de trabajos fallidos con registro de errores
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Revierte la migración eliminando las tablas de colas.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
