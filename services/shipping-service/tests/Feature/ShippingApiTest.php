<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShippingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_methods_endpoint_returns_active_records(): void
    {
        DB::table('shipping_methods')->insert([
            [
                'name' => 'Entrega Hoy',
                'description' => 'Entrega el mismo dia',
                'base_cost' => 12000,
                'delivery_time' => '1 dia',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'free_shipping_threshold' => null,
                'available_cities' => null,
                'estimated_days_min' => 1,
                'estimated_days_max' => 1,
                'city' => 'Medellin',
                'free_shipping_minimum' => null,
                'icon' => 'fas fa-truck',
                'trial554' => null,
            ],
            [
                'name' => 'Metodo Inactivo',
                'description' => 'No disponible',
                'base_cost' => 5000,
                'delivery_time' => '3 dias',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
                'free_shipping_threshold' => null,
                'available_cities' => null,
                'estimated_days_min' => 3,
                'estimated_days_max' => 4,
                'city' => 'Medellin',
                'free_shipping_minimum' => null,
                'icon' => 'fas fa-box',
                'trial554' => null,
            ],
        ]);

        $this->getJson('/api/shipping/methods')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Entrega Hoy')
            ->assertJsonMissing(['name' => 'Metodo Inactivo']);
    }

    public function test_estimate_uses_matching_price_rule(): void
    {
        DB::table('shipping_price_rules')->insert([
            [
                'min_price' => 0,
                'max_price' => 99999,
                'shipping_cost' => 9000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'trial554' => null,
            ],
            [
                'min_price' => 100000,
                'max_price' => null,
                'shipping_cost' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'trial554' => null,
            ],
        ]);

        $this->postJson('/api/shipping/estimate', [
            'subtotal' => 120000,
            'city' => 'Medellin',
        ])->assertOk()->assertJsonPath('shipping_cost', '0.00');
    }
}
