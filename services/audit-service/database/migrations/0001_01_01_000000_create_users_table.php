<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_categories', function (Blueprint $table) {
            $table->increments('audit_id');
            $table->unsignedInteger('category_id')->nullable();
            $table->string('action_type', 10)->nullable();
            $table->string('old_name', 100)->nullable();
            $table->string('new_name', 100)->nullable();
            $table->timestamp('action_date')->useCurrent();
            $table->char('trial548', 1)->nullable();
        });

        Schema::create('audit_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('orden_id');
            $table->string('accion', 10);
            $table->string('usuario_id', 20)->nullable();
            $table->string('sql_usuario', 255)->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->text('detalles')->nullable();
            $table->char('trial548', 1)->nullable();

            $table->index('orden_id');
        });

        Schema::create('audit_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usuario_id', 20);
            $table->string('accion', 10);
            $table->string('usuario_modificador', 20)->nullable();
            $table->string('sql_usuario', 255)->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->text('detalles')->nullable();
            $table->char('trial548', 1)->nullable();

            $table->index('usuario_id');
        });

        Schema::create('productos_auditoria', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('accion', 50)->default('Creado');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->char('trial554', 1)->nullable();
        });

        Schema::create('eliminaciones_auditoria', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('accion', 50)->default('Eliminado');
            $table->timestamp('fecha_eliminacion')->useCurrent();
            $table->char('trial551', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eliminaciones_auditoria');
        Schema::dropIfExists('productos_auditoria');
        Schema::dropIfExists('audit_users');
        Schema::dropIfExists('audit_orders');
        Schema::dropIfExists('audit_categories');
    }
};
