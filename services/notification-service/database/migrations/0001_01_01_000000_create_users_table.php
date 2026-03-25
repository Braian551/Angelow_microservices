<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('description', 255)->nullable();
            $table->text('template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->unsignedInteger('type_id');
            $table->string('title', 100);
            $table->text('message');
            $table->string('related_entity_type', 30)->nullable();
            $table->unsignedInteger('related_entity_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_email_sent')->default(false);
            $table->boolean('is_sms_sent')->default(false);
            $table->boolean('is_push_sent')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('read_at')->nullable();
            $table->char('trial551', 1)->nullable();

            $table->index('user_id');
            $table->index('type_id');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id', 20);
            $table->unsignedInteger('type_id');
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial551', 1)->nullable();

            $table->unique(['user_id', 'type_id']);
        });

        Schema::create('notification_queue', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('notification_id');
            $table->string('channel', 10);
            $table->string('status', 20)->default('pending');
            $table->smallInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('scheduled_at')->useCurrent();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->char('trial551', 1)->nullable();

            $table->index('notification_id');
            $table->index('status');
        });

        Schema::create('admin_notification_dismissals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('admin_id', 20);
            $table->string('notification_key', 120);
            $table->timestamp('dismissed_at')->useCurrent();
            $table->char('trial548', 1)->nullable();

            $table->unique(['admin_id', 'notification_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_dismissals');
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_types');
    }
};
