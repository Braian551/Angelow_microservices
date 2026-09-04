<?php

namespace Tests\Feature;

use App\Models\CourierProfile;
use App\Models\DeliveryAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourierDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'courier-delivery-token';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('courier_token_' . hash('sha256', self::TOKEN), [
            'id' => 'courier-delivery-1',
            'email' => 'repartidor@example.com',
            'role' => 'courier',
        ]);
    }

    public function test_approved_courier_receives_runtime_map_configuration_without_cache(): void
    {
        $this->approvedCourier();
        config(['services.mapbox.access_token' => 'pk.test-runtime-token']);

        $response = $this->withToken(self::TOKEN)
            ->getJson('/api/courier/map-config')
            ->assertOk()
            ->assertJsonPath('data.access_token', 'pk.test-runtime-token');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_accepting_delivery_requests_customer_notification_and_email_with_code(): void
    {
        $profile = $this->approvedCourier();
        config([
            'services.orders.base_url' => 'http://order-service.test/api',
            'services.notifications.base_url' => 'http://notification-service.test/api',
            'services.auth.base_url' => 'http://auth-service.test/api',
        ]);
        Http::fake([
            'http://order-service.test/api/*' => Http::response(['message' => 'ok']),
            'http://notification-service.test/api/notifications' => Http::response(['message' => 'ok'], 201),
            'http://auth-service.test/api/*' => Http::response(['data' => []]),
        ]);

        $assignment = DeliveryAssignment::query()->create([
            'order_id' => 700,
            'order_number' => 'ORD-700',
            'customer_user_id' => 'customer-700',
            'customer_email' => 'cliente700@example.com',
            'shipping_method_name' => 'Envío estándar',
            'delivery_time' => 'Hoy',
            'destination_address' => 'Carrera 70 # 10-20',
            'destination_city' => 'Medellín',
            'status' => 'pending',
        ]);

        $this->withToken(self::TOKEN)
            ->postJson("/api/courier/assignments/{$assignment->id}/accept")
            ->assertOk()
            ->assertJsonPath('customer_notified', true)
            ->assertJsonPath('data.courier_profile_id', $profile->id)
            ->assertJsonPath('data.status', 'assigned');

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'http://notification-service.test/api/notifications'
                && ($payload['user_email'] ?? null) === 'cliente700@example.com'
                && ($payload['send_email'] ?? false) === true
                && ($payload['send_push'] ?? false) === true
                && preg_match('/Código de entrega: \d{6}\./u', (string) ($payload['message'] ?? '')) === 1;
        });
    }

    private function approvedCourier(): CourierProfile
    {
        return CourierProfile::query()->create([
            'user_id' => 'courier-delivery-1',
            'email' => 'repartidor@example.com',
            'document_type' => 'cc',
            'document_number' => '1000000001',
            'document_number_hash' => hash('sha256', 'cc:1000000001'),
            'birth_date' => '1990-01-01',
            'phone' => '3001234567',
            'address' => 'Calle 10 # 20-30',
            'status' => 'approved',
            'is_active' => true,
            'terms_version' => '2026-07-22',
            'terms_accepted_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => 'admin-1',
        ]);
    }
}
