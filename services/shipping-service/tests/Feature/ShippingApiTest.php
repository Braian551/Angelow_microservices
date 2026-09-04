<?php

namespace Tests\Feature;

use App\Models\CourierLocation;
use App\Models\DeliveryAssignment;
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

    public function test_paid_home_delivery_can_be_published_once_for_couriers(): void
    {
        config(['services.internal_token' => 'test-internal-token']);
        $methodId = DB::table('shipping_methods')->insertGetId([
            'name' => 'Entrega prioritaria',
            'description' => 'Entrega a domicilio',
            'base_cost' => 12000,
            'delivery_time' => '2 horas',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'order_id' => 501,
            'order_number' => 'ANG-501',
            'customer_user_id' => 'customer-1',
            'customer_email' => 'cliente@example.com',
            'shipping_method_id' => $methodId,
            'destination_address' => 'Calle 10 # 20-30',
            'destination_city' => 'Medellín',
            'destination_latitude' => 6.2442,
            'destination_longitude' => -75.5812,
        ];

        $this->withHeader('X-Internal-Token', 'test-internal-token')
            ->postJson('/api/internal/deliveries/eligible', $payload)
            ->assertCreated()
            ->assertJsonPath('eligible', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.delivery_time', '2 horas');

        $this->withHeader('X-Internal-Token', 'test-internal-token')
            ->postJson('/api/internal/deliveries/eligible', $payload)
            ->assertCreated();

        $this->assertDatabaseCount('delivery_assignments', 1);
    }

    public function test_pickup_method_is_not_published_for_couriers(): void
    {
        config(['services.internal_token' => 'test-internal-token']);
        $methodId = DB::table('shipping_methods')->insertGetId([
            'name' => 'Punto de entrega',
            'description' => 'Recogida por el cliente',
            'base_cost' => 0,
            'delivery_time' => 'Disponible hoy',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('X-Internal-Token', 'test-internal-token')
            ->postJson('/api/internal/deliveries/eligible', [
                'order_id' => 502,
                'shipping_method_id' => $methodId,
            ])
            ->assertOk()
            ->assertJsonPath('eligible', false);

        $this->assertDatabaseCount('delivery_assignments', 0);
    }

    public function test_delivery_recovers_exact_coordinates_from_saved_address(): void
    {
        config(['services.internal_token' => 'test-internal-token']);
        $addressId = DB::table('user_addresses')->insertGetId([
            'user_id' => 'customer-9',
            'alias' => 'Casa',
            'recipient_name' => 'Cliente de prueba',
            'recipient_phone' => '3001234567',
            'address' => 'Calle 59, Medellín',
            'neighborhood' => 'Villa Hermosa',
            'gps_latitude' => 6.252895,
            'gps_longitude' => -75.538951,
            'gps_used' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('X-Internal-Token', 'test-internal-token')
            ->postJson('/api/internal/deliveries/eligible', [
                'order_id' => 505,
                'order_number' => 'ANG-505',
                'shipping_address_id' => $addressId,
                'shipping_method_name' => 'Envío estándar',
            ])
            ->assertCreated()
            ->assertJsonPath('data.destination_latitude', 6.252895)
            ->assertJsonPath('data.destination_longitude', -75.538951);
    }

    public function test_method_snapshot_publishes_delivery_without_distributed_method_id(): void
    {
        config(['services.internal_token' => 'test-internal-token']);

        $this->withHeader('X-Internal-Token', 'test-internal-token')
            ->postJson('/api/internal/deliveries/eligible', [
                'order_id' => 504,
                'order_number' => 'ANG-504',
                'shipping_method_id' => null,
                'shipping_method_name' => 'Envío estándar',
                'delivery_time' => 'Coordinaremos el despacho contigo',
                'destination_address' => 'Carrera 67 # 10-20',
                'destination_city' => 'Medellín',
            ])
            ->assertCreated()
            ->assertJsonPath('eligible', true)
            ->assertJsonPath('data.shipping_method_id', null)
            ->assertJsonPath('data.shipping_method_name', 'Envío estándar')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('delivery_assignments', [
            'order_id' => 504,
            'shipping_method_name' => 'Envío estándar',
            'status' => 'pending',
        ]);
    }

    public function test_customer_tracking_only_exposes_consented_active_location(): void
    {
        $assignment = DeliveryAssignment::query()->create([
            'order_id' => 503,
            'customer_user_id' => 'customer-3',
            'customer_email' => 'cliente3@example.com',
            'status' => 'en_route',
            'sharing_location' => false,
            'delivery_code' => '654321',
            'delivery_code_hash' => bcrypt('654321'),
        ]);
        CourierLocation::query()->create([
            'delivery_assignment_id' => $assignment->id,
            'latitude' => 6.2442,
            'longitude' => -75.5812,
            'recorded_at' => now(),
        ]);

        $this->getJson('/api/shipping/deliveries/orders/503/tracking?user_id=customer-3')
            ->assertOk()
            ->assertJsonPath('data.sharing_location', false)
            ->assertJsonPath('data.location', null)
            ->assertJsonPath('data.delivery_code', '654321');

        $assignment->update(['sharing_location' => true]);

        $this->getJson('/api/shipping/deliveries/orders/503/tracking?user_id=customer-3')
            ->assertOk()
            ->assertJsonPath('data.sharing_location', true)
            ->assertJsonPath('data.location.latitude', 6.2442);

        $this->getJson('/api/shipping/deliveries/orders/503/tracking?user_id=other')
            ->assertForbidden();
    }
}
