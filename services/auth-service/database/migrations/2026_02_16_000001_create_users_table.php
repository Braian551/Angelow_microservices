<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create users table
 *
 * Mirrors the legacy Angelow users table schema.
 * Uses varchar(20) primary key for backward compatibility with the
 * monolith's uniqid()-based IDs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 15)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->enum('role', ['customer', 'admin'])->default('customer');
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
            $table->datetime('last_access')->nullable();
            $table->string('remember_token', 255)->nullable();
            $table->datetime('token_expiry')->nullable();
            $table->char('trial548', 1)->nullable();

            // Indexes
            $table->index('email');
            $table->index('phone');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
