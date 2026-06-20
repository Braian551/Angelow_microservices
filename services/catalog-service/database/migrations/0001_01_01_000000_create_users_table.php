<?php

// Comentario de mantenimiento: Esta migración describe la estructura persistente necesaria para el dominio.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea o ajusta tablas requeridas por el servicio.
     */
    public function up(): void
    {
        // Crea la tabla categories con los campos necesarios para este flujo del dominio.
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla collections con los campos necesarios para este flujo del dominio.

        Schema::create('collections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->date('launch_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla colors con los campos necesarios para este flujo del dominio.

        Schema::create('colors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('hex_code', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        // Crea la tabla sizes con los campos necesarios para este flujo del dominio.

        Schema::create('sizes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('description', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();
        });

        // Crea la tabla products con los campos necesarios para este flujo del dominio.

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('gender', 20)->default('unisex');
            $table->string('collection', 50)->nullable();
            $table->string('material', 100)->nullable();
            $table->text('care_instructions')->nullable();
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('collection_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('category_id');
            $table->index('collection_id');
        });

        // Crea la tabla product_collections con los campos necesarios para este flujo del dominio.

        Schema::create('product_collections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('collection_id');
            $table->integer('display_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('product_id');
            $table->index('collection_id');
        });

        // Crea la tabla product_color_variants con los campos necesarios para este flujo del dominio.

        Schema::create('product_color_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('color_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->char('trial554', 1)->nullable();

            $table->index('product_id');
        });

        // Crea la tabla product_size_variants con los campos necesarios para este flujo del dominio.

        Schema::create('product_size_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('color_variant_id');
            $table->unsignedInteger('size_id')->nullable();
            $table->string('sku', 50)->nullable();
            $table->string('barcode', 50)->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->char('trial554', 1)->nullable();

            $table->index('color_variant_id');
            $table->index('size_id');
        });

        // Crea la tabla product_images con los campos necesarios para este flujo del dominio.

        Schema::create('product_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('color_variant_id')->nullable();
            $table->string('image_path', 255);
            $table->string('alt_text', 255)->nullable();
            $table->integer('order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('is_primary')->default(false);
            $table->char('trial554', 1)->nullable();

            $table->index('product_id');
        });

        // Crea la tabla variant_images con los campos necesarios para este flujo del dominio.

        Schema::create('variant_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('color_variant_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('image_id')->nullable();
            $table->string('image_path', 255);
            $table->string('alt_text', 255)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial558', 1)->nullable();

            $table->index('color_variant_id');
            $table->index('product_id');
        });

        // Crea la tabla wishlist con los campos necesarios para este flujo del dominio.

        Schema::create('wishlist', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->unsignedInteger('product_id');
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial558', 1)->nullable();

            $table->unique(['user_id', 'product_id']);
        });

        // Crea la tabla product_reviews con los campos necesarios para este flujo del dominio.

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('user_id', 20);
            $table->unsignedInteger('order_id')->nullable();
            $table->smallInteger('rating');
            $table->string('title', 100);
            $table->text('comment');
            $table->text('images')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('product_id');
            $table->index('user_id');
        });

        // Crea la tabla review_votes con los campos necesarios para este flujo del dominio.

        Schema::create('review_votes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('review_id');
            $table->string('user_id', 20);
            $table->boolean('is_helpful');
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->unique(['review_id', 'user_id']);
        });

        // Crea la tabla product_questions con los campos necesarios para este flujo del dominio.

        Schema::create('product_questions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('user_id', 20);
            $table->text('question');
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('product_id');
        });

        // Crea la tabla question_answers con los campos necesarios para este flujo del dominio.

        Schema::create('question_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('question_id');
            $table->string('user_id', 20);
            $table->text('answer');
            $table->boolean('is_seller')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('question_id');
        });

        // Crea la tabla popular_searches con los campos necesarios para este flujo del dominio.

        Schema::create('popular_searches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('search_term', 255);
            $table->integer('search_count')->default(1);
            $table->timestamp('last_searched')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->unique('search_term');
        });

        // Crea la tabla search_history con los campos necesarios para este flujo del dominio.

        Schema::create('search_history', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20)->nullable();
            $table->string('search_term', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('user_id');
        });

        // Crea la tabla site_settings con los campos necesarios para este flujo del dominio.

        Schema::create('site_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('setting_key', 120)->unique();
            $table->text('setting_value')->nullable();
            $table->string('category', 40)->default('general');
            $table->string('updated_by', 64)->nullable();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial554', 1)->nullable();
        });

        // Crea la tabla sliders con los campos necesarios para este flujo del dominio.

        Schema::create('sliders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->string('image', 500);
            $table->string('link', 500)->nullable();
            $table->integer('order_position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial558', 1)->nullable();
        });

        // Crea la tabla announcements con los campos necesarios para este flujo del dominio.

        Schema::create('announcements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 12);
            $table->string('title', 255);
            $table->text('message');
            $table->string('subtitle', 255)->nullable();
            $table->string('button_text', 100)->nullable();
            $table->string('button_link', 500)->nullable();
            $table->string('image', 500)->nullable();
            $table->string('background_color', 20)->default('#000000');
            $table->string('text_color', 20)->default('#ffffff');
            $table->string('icon', 50)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial548', 1)->nullable();
        });

        // Crea la tabla stock_history con los campos necesarios para este flujo del dominio.

        Schema::create('stock_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('variant_id');
            $table->string('user_id', 20);
            $table->integer('previous_qty');
            $table->integer('new_qty');
            $table->string('operation', 12);
            $table->text('notes')->nullable();
            $table->timestamp('created_at');
            $table->char('trial558', 1)->nullable();

            $table->index('variant_id');
        });
    }

    /**
     * Revierte las tablas creadas por esta migración en orden seguro.
     */

    public function down(): void
    {
        // Revierte la tabla stock_history al deshacer la migración.
        Schema::dropIfExists('stock_history');
        // Revierte la tabla announcements al deshacer la migración.
        Schema::dropIfExists('announcements');
        // Revierte la tabla sliders al deshacer la migración.
        Schema::dropIfExists('sliders');
        // Revierte la tabla site_settings al deshacer la migración.
        Schema::dropIfExists('site_settings');
        // Revierte la tabla search_history al deshacer la migración.
        Schema::dropIfExists('search_history');
        // Revierte la tabla popular_searches al deshacer la migración.
        Schema::dropIfExists('popular_searches');
        // Revierte la tabla question_answers al deshacer la migración.
        Schema::dropIfExists('question_answers');
        // Revierte la tabla product_questions al deshacer la migración.
        Schema::dropIfExists('product_questions');
        // Revierte la tabla review_votes al deshacer la migración.
        Schema::dropIfExists('review_votes');
        // Revierte la tabla product_reviews al deshacer la migración.
        Schema::dropIfExists('product_reviews');
        // Revierte la tabla wishlist al deshacer la migración.
        Schema::dropIfExists('wishlist');
        // Revierte la tabla variant_images al deshacer la migración.
        Schema::dropIfExists('variant_images');
        // Revierte la tabla product_images al deshacer la migración.
        Schema::dropIfExists('product_images');
        // Revierte la tabla product_size_variants al deshacer la migración.
        Schema::dropIfExists('product_size_variants');
        // Revierte la tabla product_color_variants al deshacer la migración.
        Schema::dropIfExists('product_color_variants');
        // Revierte la tabla product_collections al deshacer la migración.
        Schema::dropIfExists('product_collections');
        // Revierte la tabla products al deshacer la migración.
        Schema::dropIfExists('products');
        // Revierte la tabla sizes al deshacer la migración.
        Schema::dropIfExists('sizes');
        // Revierte la tabla colors al deshacer la migración.
        Schema::dropIfExists('colors');
        // Revierte la tabla collections al deshacer la migración.
        Schema::dropIfExists('collections');
        // Revierte la tabla categories al deshacer la migración.
        Schema::dropIfExists('categories');
    }
};
