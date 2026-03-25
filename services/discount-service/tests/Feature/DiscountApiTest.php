<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiscountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_returns_valid_for_active_code(): void
    {
        $typeId = DB::table('discount_types')->insertGetId([
            'name' => 'Porcentaje',
            'description' => 'Descuento porcentual',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'trial551' => null,
        ]);

        DB::table('discount_codes')->insert([
            'code' => 'PROMO10',
            'discount_type_id' => $typeId,
            'discount_value' => 10,
            'max_uses' => 100,
            'used_count' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
            'is_single_use' => false,
            'created_by' => '1',
            'created_at' => now(),
            'updated_at' => now(),
            'trial551' => null,
        ]);

        $this->postJson('/api/discounts/validate', [
            'code' => 'promo10',
            'user_id' => '10',
            'order_total' => 200000,
        ])->assertOk()->assertJsonPath('valid', true);
    }

    public function test_validate_returns_error_for_expired_code(): void
    {
        $typeId = DB::table('discount_types')->insertGetId([
            'name' => 'Fijo',
            'description' => 'Descuento fijo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'trial551' => null,
        ]);

        DB::table('discount_codes')->insert([
            'code' => 'VENCIDO',
            'discount_type_id' => $typeId,
            'discount_value' => 20000,
            'max_uses' => 10,
            'used_count' => 0,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDay(),
            'is_active' => true,
            'is_single_use' => false,
            'created_by' => '1',
            'created_at' => now(),
            'updated_at' => now(),
            'trial551' => null,
        ]);

        $this->postJson('/api/discounts/validate', [
            'code' => 'VENCIDO',
        ])->assertStatus(422)->assertJsonPath('message', 'Codigo expirado');
    }
}
