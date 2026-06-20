<?php

/*
|--------------------------------------------------------------------------
| Migración correctiva: tokenable_id como string en personal_access_tokens
|--------------------------------------------------------------------------
|
| Laravel Sanctum por defecto espera que tokenable_id sea BIGINT,
| pero el modelo User del auth-service usa IDs alfanuméricos (string)
| heredados del legacy. Esta migración corrige el tipo de columna
| según el motor de BD (PostgreSQL o MySQL) para que Sanctum funcione
| correctamente con IDs de tipo uniqid().
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cambia tokenable_id de BIGINT a VARCHAR(20).
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

    /**
     * Revierte el cambio: restaura tokenable_id a BIGINT.
     */
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
