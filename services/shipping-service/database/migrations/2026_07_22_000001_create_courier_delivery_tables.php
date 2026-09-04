<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id', 20)->unique();
            $table->string('email', 100)->index();
            $table->string('document_type', 20);
            $table->text('document_number');
            $table->char('document_number_hash', 64)->unique();
            $table->date('birth_date');
            $table->string('phone', 15);
            $table->string('address', 180);
            $table->string('city', 100);
            $table->string('emergency_contact_name', 100);
            $table->string('emergency_contact_phone', 15);
            $table->string('work_modality', 20);
            $table->string('eps_name', 100)->nullable();
            $table->string('pension_fund', 100)->nullable();
            $table->string('arl_status', 30)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->string('terms_version', 30);
            $table->timestamp('terms_accepted_at');
            $table->timestamp('data_authorization_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewed_by', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('courier_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('make_id', 40)->nullable();
            $table->string('make_name', 100)->nullable();
            $table->string('model_id', 40)->nullable();
            $table->string('model_name', 100)->nullable();
            $table->string('color_name', 60)->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('plate', 12)->nullable()->index();
            $table->string('ownership_type', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('courier_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('path', 255);
            $table->date('expires_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['courier_profile_id', 'type']);
        });

        Schema::create('delivery_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('order_number', 30)->nullable();
            $table->string('order_source', 20)->nullable();
            $table->foreignId('courier_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_user_id', 40)->nullable()->index();
            $table->string('customer_email', 100)->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->string('shipping_method_name', 100)->nullable();
            $table->string('delivery_time', 80)->nullable();
            $table->text('destination_address')->nullable();
            $table->string('destination_city', 100)->nullable();
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('delivery_code_hash', 255)->nullable();
            $table->text('delivery_code')->nullable();
            $table->boolean('sharing_location')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('route_started_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_assignment_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('heading', 6, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->index(['delivery_assignment_id', 'recorded_at']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('courier_locations');
        Schema::dropIfExists('delivery_assignments');
        Schema::dropIfExists('courier_documents');
        Schema::dropIfExists('courier_vehicles');
        Schema::dropIfExists('courier_profiles');
    }
};
