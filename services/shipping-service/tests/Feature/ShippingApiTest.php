<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de verificación para los endpoints del servicio de envíos.
 *
 * Valida el comportamiento de:
 * - Listado de métodos de envío (filtra solo activos)
 * - Estimación de costo de envío usando reglas de precio por rango
 * - Combinación de costo base + regla de precio en métodos
 *
 * Usa RefreshDatabase para mantener el entorno de pruebas limpio.
 */
class ShippingApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: El endpoint de métodos retorna solo registros activos.
     *
     * Crea un método activo y uno inactivo, luego verifica que
     * la respuesta solo contenga el activo.
     */
    public function test_methods_endpoint_returns_active_records(): void
    {
        // Inserta dos métodos: uno activo y uno inactivo
        DB::table('shipping_methods')->insert([
            [
                'name' => 'Entrega Hoy',
                'description' => 'Entrega el mismo día',
                'base_cost' => 12000,
                'delivery_time' => '1 día',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'free_shipping_threshold' => null,
                'available_cities' => null,
                'estimated_days_min' => 1,
                'estimated_days_max' => 1,
                'city' => 'Medellín',
                'free_shipping_minimum' => null,
                'icon' => 'fas fa-truck',
                'trial554' => null,
            ],
            [
                'name' => 'Método Inactivo',
                'description' => 'No disponible',
                'base_cost' => 5000,
                'delivery_time' => '3 días',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
                'free_shipping_threshold' => null,
                'available_cities' => null,
                'estimated_days_min' => 3,
                'estimated_days_max' => 4,
                'city' => 'Medellín',
                'free_shipping_minimum' => null,
                'icon' => 'fas fa-box',
                'trial554' => null,
            ],
        ]);

        // Verifica que solo el método activo aparezca en la respuesta
        $this->getJson('/api/shipping/methods')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Entrega Hoy')
            ->assertJsonMissing(['name' => 'Método Inactivo']);
    }

    /**
     * Test: La estimación usa la regla de precio que coincide con el subtotal.
     *
     * Crea dos reglas: una para subtotales < $100.000 (costo $9.000)
     * y otra para subtotales >= $100.000 (costo $0).
     * Envía un subtotal de $120.000 y verifica que el costo sea $0
     * y la etiqueta del rango sea "Desde $100.000".
     */
    public function test_estimate_uses_matching_price_rule(): void
    {
        // Reglas de precio: menor a 100k → $9.000, desde 100k → $0
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

        // Verifica que la regla correcta se aplique al subtotal de 120k
        $this->postJson('/api/shipping/estimate', [
            'subtotal' => 120000,
            'city' => 'Medellín',
        ])
            ->assertOk()
            ->assertJsonPath('shipping_cost', 0)
            ->assertJsonPath('range_rule_additional_cost', 0)
            ->assertJsonPath('range_rule_label', 'Desde $100.000');
    }

    /**
     * Test: El endpoint de métodos combina costo base + regla de precio.
     *
     * Crea un método con costo base $5.000 y una regla de $0-$99.999
     * con costo adicional $9.000. Con subtotal de $20.000, el costo
     * total debe ser $5.000 + $9.000 = $14.000, y la fuente debe
     * ser 'base_plus_price_rule'.
     */
    public function test_methods_endpoint_adds_price_rule_cost_when_subtotal_matches(): void
    {
        // Método de envío con costo base $5.000
        DB::table('shipping_methods')->insert([
            'name' => 'Envío principal',
            'description' => 'Método de prueba',
            'base_cost' => 5000,
            'delivery_time' => '2 días',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'free_shipping_threshold' => null,
            'available_cities' => null,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
            'city' => 'Medellín',
            'free_shipping_minimum' => 0,
            'icon' => 'fas fa-truck',
            'trial554' => null,
        ]);

        // Regla de precio: $0 a $99.999 → $9.000 adicional
        DB::table('shipping_price_rules')->insert([
            'min_price' => 0,
            'max_price' => 99999,
            'shipping_cost' => 9000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'trial554' => null,
        ]);

        // Con subtotal $20.000, debe combinar base + regla
        $this->getJson('/api/shipping/methods?subtotal=20000')
            ->assertOk()
            ->assertJsonPath('data.0.method_cost', 5000)
            ->assertJsonPath('data.0.range_rule_additional_cost', 9000)
            ->assertJsonPath('data.0.applied_cost', 14000)
            ->assertJsonPath('data.0.pricing_source', 'base_plus_price_rule');
    }
}
