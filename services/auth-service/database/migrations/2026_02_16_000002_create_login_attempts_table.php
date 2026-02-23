<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create login_attempts table
 *
 * Tracks failed login attempts for brute-force protection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('ip_address', 45);
            $table->datetime('attempt_date');

            $table->index(['username', 'attempt_date']);
            $table->index(['ip_address', 'attempt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
