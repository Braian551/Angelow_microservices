<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para la tabla de direcciones de usuario en shipping-db (distribuida).
 *
 * Crea la tabla user_addresses en la base de datos del microservicio de envíos.
 * Es la tabla destino de la migración, con un esquema normalizado que difiere
 * ligeramente del legacy (usa address_line_1/address_line_2 en lugar de
 * address/complement, y la tabla se identifica por ID autoincremental).
 *
 * La migración verifica si la tabla ya existe antes de crearla para evitar
 * conflictos cuando el esquema ya fue inicializado por otros medios.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración creando la tabla si no existe.
     */
    public function up(): void
    {
        // Evita duplicados si la tabla ya fue creada
        if (Schema::hasTable('user_addresses')) {
            return;
        }

        Schema::create('user_addresses', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id', 20);             // ID del usuario propietario
            $table->string('recipient_name', 100);     // Nombre del destinatario
            $table->string('phone', 15)->nullable();   // Teléfono de contacto
            $table->string('address_line_1', 180);     // Dirección principal
            $table->string('address_line_2', 180)->nullable();  // Complemento
            $table->string('city', 100);               // Ciudad
            $table->string('department', 100)->nullable();  // Departamento
            $table->string('postal_code', 20)->nullable();  // Código postal
            $table->string('country', 100)->default('Colombia');  // País
            $table->text('notes')->nullable();         // Instrucciones de entrega
            $table->boolean('is_default')->default(false);  // Es predeterminada
            $table->boolean('is_active')->default(true);    // Está activa
            $table->timestamps();                      // created_at, updated_at

            $table->index('user_id');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Revierte la migración eliminando la tabla.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};