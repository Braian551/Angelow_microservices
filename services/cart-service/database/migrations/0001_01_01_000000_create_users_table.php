<?php

/*
|--------------------------------------------------------------------------
| Migración principal del cart-service: carts + cart_items
|--------------------------------------------------------------------------
|
| Crea las tablas de dominio del carrito de compras:
| - carts: representa un carrito asociado a un usuario (user_id) o sesión
|          anónima (session_id). user_id es string(50) para compatibilidad
|          con el ID alfanumérico del auth-service.
| - cart_items: ítems individuales dentro de un carrito, cada uno con
|          producto, variante de talla y color opcional, y cantidad.
|
| Las columnas trial551 y trial548 son campos legacy de la migración
| original de Angelow; se mantienen por compatibilidad con datos previos.
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea las tablas carts y cart_items.
     */
    public function up(): void
    {
        // Tabla de carritos: asociados a usuario o sesión anónima
        Schema::create('carts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 50)->nullable()->comment('ID del usuario (string alfanumérico del auth-service)');
            $table->string('session_id')->nullable()->comment('ID de sesión para visitantes no autenticados');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable()->comment('Campo legacy de migración original');
        });

        // Tabla de ítems del carrito: productos con variantes y cantidad
        Schema::create('cart_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id')->comment('Carrito al que pertenece este ítem');
            $table->unsignedInteger('product_id')->comment('ID del producto en catalog-service');
            $table->unsignedInteger('color_variant_id')->nullable()->comment('ID de variante de color (opcional)');
            $table->unsignedInteger('size_variant_id')->nullable()->comment('ID de variante de talla');
            $table->integer('quantity')->default(1)->comment('Cantidad de unidades');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial548', 1)->nullable()->comment('Campo legacy de migración original');

            $table->index('cart_id');
            $table->index('product_id');
        });
    }

    /**
     * Revierte la migración: elimina las tablas del carrito.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
