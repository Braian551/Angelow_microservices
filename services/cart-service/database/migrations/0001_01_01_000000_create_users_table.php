<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 50)->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('color_variant_id')->nullable();
            $table->unsignedInteger('size_variant_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial548', 1)->nullable();

            $table->index('cart_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
