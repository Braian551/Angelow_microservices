<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REMOVED_COLUMNS = [
        'city',
        'emergency_contact_name',
        'emergency_contact_phone',
        'work_modality',
        'eps_name',
        'pension_fund',
        'arl_status',
        'data_authorization_at',
    ];

    public function up(): void
    {
        if (Schema::hasTable('courier_documents')) {
            DB::table('courier_documents')
                ->where('type', 'social_security')
                ->orderBy('id')
                ->each(function (object $document): void {
                    $path = str_replace('\\', '/', ltrim((string) $document->path, '/'));
                    if (preg_match('#^uploads/couriers/[0-9]+/[^/]+$#', $path) === 1) {
                        File::delete(public_path($path));
                    }
                });

            DB::table('courier_documents')->where('type', 'social_security')->delete();
        }

        if (!Schema::hasTable('courier_profiles')) {
            return;
        }

        $columns = array_values(array_filter(
            self::REMOVED_COLUMNS,
            fn (string $column): bool => Schema::hasColumn('courier_profiles', $column),
        ));

        if ($columns !== []) {
            Schema::table('courier_profiles', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('courier_profiles')) {
            return;
        }

        Schema::table('courier_profiles', function (Blueprint $table): void {
            if (!Schema::hasColumn('courier_profiles', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (!Schema::hasColumn('courier_profiles', 'emergency_contact_name')) {
                $table->string('emergency_contact_name', 100)->nullable();
            }
            if (!Schema::hasColumn('courier_profiles', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 15)->nullable();
            }
            if (!Schema::hasColumn('courier_profiles', 'work_modality')) {
                $table->string('work_modality', 20)->nullable();
            }
            if (!Schema::hasColumn('courier_profiles', 'eps_name')) {
                $table->string('eps_name', 100)->nullable();
            }
            if (!Schema::hasColumn('courier_profiles', 'pension_fund')) {
                $table->string('pension_fund', 100)->nullable();
            }
            if (!Schema::hasColumn('courier_profiles', 'arl_status')) {
                $table->string('arl_status', 30)->nullable();
            }
            if (!Schema::hasColumn('courier_profiles', 'data_authorization_at')) {
                $table->timestamp('data_authorization_at')->nullable();
            }
        });
    }
};
