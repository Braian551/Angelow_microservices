<?php

/*
|--------------------------------------------------------------------------
| Migración principal del servicio de auditoría
|--------------------------------------------------------------------------
|
| Crea las cinco tablas de trazabilidad del sistema. Cada tabla
| corresponde a un dominio auditado: categorías, órdenes, usuarios,
| productos y eliminaciones. El esquema refleja la nomenclatura
| y estructura heredada del sistema legacy Angelow.
|
| Las columnas `trialXXX` son remanentes de la base original;
| se mantienen por compatibilidad durante la migración.
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea las tablas de auditoría.
     */
    public function up(): void
    {
        /*
         * Trazabilidad de cambios en categorías del catálogo.
         * Registra el tipo de acción (INSERT/UPDATE/DELETE) y
         * los valores antes/después del nombre de la categoría.
         */
        Schema::create('audit_categories', function (Blueprint $table) {
            $table->increments('audit_id');
            $table->unsignedInteger('category_id')->nullable();
            $table->string('action_type', 10)->nullable();
            $table->string('old_name', 100)->nullable();
            $table->string('new_name', 100)->nullable();
            $table->timestamp('action_date')->useCurrent();
            $table->char('trial548', 1)->nullable();
        });

        /*
         * Auditoría de órdenes/pedidos.
         * Cada fila representa una operación sobre un pedido,
         * incluyendo qué usuario la ejecutó, desde qué sesión SQL
         * y detalles adicionales del cambio.
         */
        Schema::create('audit_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('orden_id');
            $table->string('accion', 10);
            $table->string('usuario_id', 20)->nullable();
            $table->string('sql_usuario', 255)->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->text('detalles')->nullable();
            $table->char('trial548', 1)->nullable();

            $table->index('orden_id');
        });

        /*
         * Auditoría de usuarios.
         * Almacena qué acción se realizó sobre cada usuario,
         * quién la ejecutó (usuario_modificador) y desde qué
         * contexto SQL.
         */
        Schema::create('audit_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usuario_id', 20);
            $table->string('accion', 10);
            $table->string('usuario_modificador', 20)->nullable();
            $table->string('sql_usuario', 255)->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->text('detalles')->nullable();
            $table->char('trial548', 1)->nullable();

            $table->index('usuario_id');
        });

        /*
         * Trazabilidad de productos.
         * Nombrada con nomenclatura legacy (productos_auditoria).
         * Registra creación y modificación de productos en el catálogo.
         */
        Schema::create('productos_auditoria', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('accion', 50)->default('Creado');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial554', 1)->nullable();
        });

        /*
         * Registro de eliminaciones de productos.
         * Tabla separada para mantener trazabilidad incluso
         * cuando el registro original ya no existe en productos_auditoria.
         */
        Schema::create('eliminaciones_auditoria', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('accion', 50)->default('Eliminado');
            $table->timestamp('fecha_eliminacion')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });
    }

    /**
     * Revierte la migración: elimina todas las tablas de auditoría.
     */
    public function down(): void
    {
        Schema::dropIfExists('eliminaciones_auditoria');
        Schema::dropIfExists('productos_auditoria');
        Schema::dropIfExists('audit_users');
        Schema::dropIfExists('audit_orders');
        Schema::dropIfExists('audit_categories');
    }
};
