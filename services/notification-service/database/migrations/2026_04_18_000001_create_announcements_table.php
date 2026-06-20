<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración que crea la tabla `announcements` para almacenar
 * anuncios (top_bar y promo_banner) del panel de administración.
 * Si la tabla ya existe, no la recrea. Si está vacía, inserta
 * un anuncio promocional por defecto.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla si no existe
     * y siembra un anuncio inicial de bienvenida.
     */
    public function up(): void
    {
        // Crea la tabla announcements solo si no existe (idempotente).
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->increments('id');
                $table->string('type', 30)->default('top_bar');         // top_bar | promo_banner
                $table->string('title', 150);                           // Título del anuncio
                $table->text('message')->nullable();                    // Mensaje principal
                $table->string('subtitle', 150)->nullable();            // Subtítulo opcional
                $table->string('button_text', 50)->nullable();          // Texto del botón CTA
                $table->string('button_link', 255)->nullable();         // URL del botón CTA
                $table->string('image', 255)->nullable();               // Ruta de imagen asociada
                $table->string('background_color', 20)->nullable();     // Color de fondo personalizado
                $table->string('text_color', 20)->nullable();           // Color de texto personalizado
                $table->string('icon', 50)->nullable();                 // Clase de ícono (FontAwesome)
                $table->integer('priority')->default(0);                // Prioridad de visualización
                $table->boolean('is_active')->default(true);            // Activo/inactivo
                $table->timestamp('start_date')->nullable();            // Inicio de vigencia
                $table->timestamp('end_date')->nullable();              // Fin de vigencia
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->char('trial548', 1)->nullable();
            });
        }

        // Si la tabla está vacía, inserta un anuncio predeterminado de demostración.
        if (Schema::hasTable('announcements') && DB::table('announcements')->count() === 0) {
            DB::table('announcements')->insert([
                'type' => 'promo_banner',
                'title' => '¡Oferta 3x2!',
                'message' => '¡Compra 2 prendas y llévate la 3ra con 50% de descuento!',
                'subtitle' => 'Válido hasta el 30 de junio o hasta agotar existencias',
                'button_text' => 'Aprovechar oferta',
                'button_link' => '/tienda/tienda.php?promo=3x2',
                'image' => null,
                'background_color' => '#ff6b6b',
                'text_color' => '#ffffff',
                'icon' => 'fa-tags',
                'priority' => 5,
                'is_active' => true,
                'start_date' => null,
                'end_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'trial548' => 'T',
            ]);
        }
    }

    /**
     * Revierte la migración eliminando la tabla announcements.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
