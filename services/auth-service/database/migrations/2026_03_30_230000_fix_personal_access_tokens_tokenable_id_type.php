<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige tokenable_id para IDs string del modelo User legacy.
     */
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');
            DB::statement(
                'ALTER TABLE personal_access_tokens
                 ALTER COLUMN tokenable_id TYPE VARCHAR(20)
                 USING tokenable_id::text'
            );
            DB::statement(
                'CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index
                 ON personal_access_tokens (tokenable_type, tokenable_id)'
            );
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('DROP INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens');
            DB::statement('ALTER TABLE personal_access_tokens MODIFY tokenable_id VARCHAR(20) NOT NULL');
            DB::statement(
                'CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index
                 ON personal_access_tokens (tokenable_type, tokenable_id)'
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');
            DB::statement(
                'ALTER TABLE personal_access_tokens
                 ALTER COLUMN tokenable_id TYPE BIGINT
                 USING tokenable_id::bigint'
            );
            DB::statement(
                'CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index
                 ON personal_access_tokens (tokenable_type, tokenable_id)'
            );
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('DROP INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens');
            DB::statement('ALTER TABLE personal_access_tokens MODIFY tokenable_id BIGINT UNSIGNED NOT NULL');
            DB::statement(
                'CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index
                 ON personal_access_tokens (tokenable_type, tokenable_id)'
            );
        }
    }
};
