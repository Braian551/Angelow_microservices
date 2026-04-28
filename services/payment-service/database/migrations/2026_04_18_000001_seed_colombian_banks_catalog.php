<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('colombian_banks')) {
            return;
        }

        $banks = [
            ['bank_code' => '001', 'bank_name' => 'Banco de Bogotá', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '002', 'bank_name' => 'Banco Popular', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '006', 'bank_name' => 'Banco Santander', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '007', 'bank_name' => 'BBVA Colombia', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '009', 'bank_name' => 'Citibank', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '012', 'bank_name' => 'Banco GNB Sudameris', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '013', 'bank_name' => 'Banco AV Villas', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '014', 'bank_name' => 'Banco de Occidente', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '019', 'bank_name' => 'Bancoomeva', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '023', 'bank_name' => 'Banco Itaú', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '031', 'bank_name' => 'Bancolombia', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '032', 'bank_name' => 'Banco Caja Social', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '040', 'bank_name' => 'Banco Agrario de Colombia', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '051', 'bank_name' => 'Bancamía', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '052', 'bank_name' => 'Banco WWB', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '053', 'bank_name' => 'Banco Falabella', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '054', 'bank_name' => 'Banco Pichincha', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '058', 'bank_name' => 'Banco ProCredit', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '059', 'bank_name' => 'Banco Mundo Mujer', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '060', 'bank_name' => 'Banco Finandina', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '061', 'bank_name' => 'Bancoomeva S.A.', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '062', 'bank_name' => 'Banco Davivienda', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '063', 'bank_name' => 'Banco Cooperativo Coopcentral', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '065', 'bank_name' => 'Banco Santander', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '101', 'bank_name' => 'Nequi', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '102', 'bank_name' => 'Daviplata', 'is_active' => true, 'trial551' => 'T'],
            ['bank_code' => '103', 'bank_name' => 'Movii', 'is_active' => true, 'trial551' => 'T'],
        ];

        DB::table('colombian_banks')->upsert($banks, ['bank_code'], ['bank_name', 'is_active', 'trial551']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('colombian_banks')) {
            return;
        }

        DB::table('colombian_banks')->whereIn('bank_code', [
            '001', '002', '006', '007', '009', '012', '013', '014', '019', '023', '031', '032', '040', '051', '052',
            '053', '054', '058', '059', '060', '061', '062', '063', '065', '101', '102', '103',
        ])->delete();
    }
};
