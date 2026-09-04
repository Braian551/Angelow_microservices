<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->index();
            }

            if (!Schema::hasColumn('orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('orders', 'completed_at')) {
                $columns[] = 'completed_at';
            }

            if (Schema::hasColumn('orders', 'delivered_at')) {
                $columns[] = 'delivered_at';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
