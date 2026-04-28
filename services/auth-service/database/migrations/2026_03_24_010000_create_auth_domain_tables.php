<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->string('token', 255);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->boolean('is_revoked')->default(false);
            $table->char('trial548', 1)->nullable();

            $table->index('user_id');
        });

        Schema::create('google_auth', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->string('google_id', 255);
            $table->string('access_token', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial551', 1)->nullable();

            $table->unique('google_id');
            $table->index('user_id');
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->string('token', 255);
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->char('trial554', 1)->nullable();

            $table->index('user_id');
            $table->index('token');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->string('user_id', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
            $table->char('trial554', 1)->nullable();

            $table->index('user_id');
            $table->index('last_activity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('google_auth');
        Schema::dropIfExists('access_tokens');
    }
};
