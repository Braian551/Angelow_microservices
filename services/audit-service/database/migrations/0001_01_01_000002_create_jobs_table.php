<?php

/*
|--------------------------------------------------------------------------
| Migración del sistema de colas (jobs) de Laravel
|--------------------------------------------------------------------------
|
| Crea las tablas `jobs`, `job_batches` y `failed_jobs` requeridas
| por el driver de cola "database". El audit-service usa Redis como
| driver principal, pero se mantienen estas tablas por compatibilidad
| con configuraciones de cola alternativas.
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea las tablas de colas.
     */
    public function up(): void
    {
        /*
         * Tabla principal de trabajos encolados.
         * Cada fila representa un job pendiente, con su payload,
         * número de intentos y estado de reserva.
         */
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        /*
         * Tabla de lotes de jobs (job batching).
         * Permite agrupar múltiples trabajos y monitorear
         * su progreso colectivo.
         */
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

        /*
         * Tabla de trabajos fallidos.
         * Almacena el payload y la excepción de cada job
         * que no pudo completarse, para debugging y reprocesamiento.
         */
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
     * Revierte la migración: elimina las tablas de colas.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
