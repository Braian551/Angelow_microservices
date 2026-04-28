<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->increments('id');
                $table->string('type', 30)->default('top_bar');
                $table->string('title', 150);
                $table->text('message')->nullable();
                $table->string('subtitle', 150)->nullable();
                $table->string('button_text', 50)->nullable();
                $table->string('button_link', 255)->nullable();
                $table->string('image', 255)->nullable();
                $table->string('background_color', 20)->nullable();
                $table->string('text_color', 20)->nullable();
                $table->string('icon', 50)->nullable();
                $table->integer('priority')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                $table->char('trial548', 1)->nullable();
            });
        }

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

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
