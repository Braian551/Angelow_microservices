<?php

namespace Tests\Feature;

use App\Jobs\ExpireStockReservationJob;
use App\Services\StockReservationRealtimePublisher;
use App\Services\StockReservationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockStockReservationService();
    }

    /**
     * Aísla las pruebas HTTP del servicio externo de catálogo y de Redis.
     */
    private function mockStockReservationService(): void
    {
        $this->mock(StockReservationService::class, function ($mock): void {
            $mock->shouldReceive('reserveForOrder')
                ->byDefault()
                ->andReturn([
                    'ok' => true,
                    'expires_at' => now()->addHours(2)->toISOString(),
                    'ttl_seconds' => 7200,
                ]);

            $mock->shouldReceive('extendReservation')
                ->byDefault()
                ->andReturn(['ok' => true, 'extended' => 1]);

            $mock->shouldReceive('confirmReservation')
                ->byDefault()
                ->andReturn(['ok' => true, 'confirmed' => 1]);

            $mock->shouldReceive('releaseReservation')
                ->byDefault()
                ->andReturn(['ok' => true, 'released' => 1]);
        });
    }

    /**
     * Construye un payload válido según el contrato actual de creación de pedidos.
     */
    private function orderPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'order_number' => 'ORD-BASE',
            'user_id' => '12',
            'subtotal' => 100000,
            'shipping_cost' => 12000,
            'total' => 112000,
            'items' => [
                [
                    'product_id' => 10,
                    'color_variant_id' => 20,
                    'size_variant_id' => 30,
                    'product_name' => 'Conjunto infantil',
                    'variant_name' => 'Azul / 4T',
                    'price' => 100000,
                    'quantity' => 1,
                    'total' => 100000,
                ],
            ],
        ], $overrides);
    }

    public function test_can_create_and_view_order(): void
    {
        $createResponse = $this->postJson('/api/orders', $this->orderPayload([
            'order_number' => 'ORD-1001',
            'user_id' => '12',
            'subtotal' => 100000,
            'total' => 112000,
        ]));

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('message', 'Orden creada');

        $orderId = (int) $createResponse->json('id');

        $this->getJson("/api/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('order.order_number', 'ORD-1001')
            ->assertJsonPath('order.user_id', '12');
    }

    public function test_can_update_order_status_and_persist_history(): void
    {
        $createResponse = $this->postJson('/api/orders', $this->orderPayload([
            'order_number' => 'ORD-2001',
            'user_id' => '50',
            'subtotal' => 85000,
            'shipping_cost' => 5000,
            'total' => 90000,
            'items' => [
                [
                    'product_id' => 11,
                    'color_variant_id' => 21,
                    'size_variant_id' => 31,
                    'product_name' => 'Vestido infantil',
                    'variant_name' => 'Rosa / 5T',
                    'price' => 85000,
                    'quantity' => 1,
                    'total' => 85000,
                ],
            ],
        ]));
        $createResponse->assertCreated();
        $orderId = (int) $createResponse->json('id');

        $this->patchJson("/api/orders/{$orderId}/status", [
            'status' => 'shipped',
            'changed_by' => '1',
            'changed_by_name' => 'admin',
            'description' => 'Cambio manual de estado',
        ])->assertOk()->assertJsonPath('message', 'Estado actualizado');

        $this->getJson("/api/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('order.status', 'shipped')
            ->assertJsonPath('history.0.new_value', 'shipped');
    }

    public function test_delivery_status_persists_the_delivery_timestamp(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        Http::fake(['*' => Http::response(['message' => 'ok'], 201)]);

        try {
            $orderId = (int) $this->postJson('/api/orders', $this->orderPayload([
                'order_number' => 'ORD-DELIVERED-AT',
            ]))->json('id');

            $this->patchJson("/api/orders/{$orderId}/status", [
                'status' => 'delivered',
                'changed_by_name' => 'Angelow Repartidor',
                'description' => 'Entrega confirmada con código del cliente.',
            ])->assertOk();

            $this->assertDatabaseHas('orders', [
                'id' => $orderId,
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_scheduler_completes_delivered_orders_after_the_refund_deadline(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        config(['services.catalog.base_url' => 'http://catalog-service.test/api']);
        Http::fake([
            'http://catalog-service.test/api/internal/products/*' => Http::response([
                'data' => ['is_refundable' => true, 'refund_days' => 1],
            ]),
            '*' => Http::response(['message' => 'ok'], 201),
        ]);

        try {
            $orderId = (int) $this->postJson('/api/orders', $this->orderPayload([
                'order_number' => 'ORD-REFUND-DEADLINE',
            ]))->json('id');

            $this->patchJson("/api/orders/{$orderId}/status", [
                'status' => 'delivered',
                'changed_by_name' => 'Angelow Repartidor',
            ])->assertOk();
            DB::table('orders')->where('id', $orderId)->update([
                'delivered_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]);

            $this->artisan('orders:complete-expired-refund-windows')
                ->expectsOutputToContain('"completed":1')
                ->assertExitCode(0);

            $this->assertDatabaseHas('orders', [
                'id' => $orderId,
                'status' => 'completed',
            ]);
            $this->assertDatabaseHas('order_status_history', [
                'order_id' => $orderId,
                'field_changed' => 'status',
                'new_value' => 'completed',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_scheduler_completes_non_refundable_delivered_orders_on_its_next_run(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        config(['services.catalog.base_url' => 'http://catalog-service.test/api']);
        Http::fake([
            'http://catalog-service.test/api/internal/products/*' => Http::response([
                'data' => ['is_refundable' => false, 'refund_days' => null],
            ]),
            '*' => Http::response(['message' => 'ok'], 201),
        ]);

        try {
            $orderId = (int) $this->postJson('/api/orders', $this->orderPayload([
                'order_number' => 'ORD-NO-REFUND-WINDOW',
            ]))->json('id');

            $this->patchJson("/api/orders/{$orderId}/status", [
                'status' => 'delivered',
                'changed_by_name' => 'Angelow Repartidor',
            ])->assertOk();

            $this->artisan('orders:complete-expired-refund-windows')
                ->expectsOutputToContain('"completed":1')
                ->assertExitCode(0);

            $this->assertDatabaseHas('orders', [
                'id' => $orderId,
                'status' => 'completed',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_status_and_payment_changes_dispatch_notification_and_email(): void
    {
        if (!Schema::hasColumn('orders', 'billing_email')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('billing_email', 255)->nullable();
                $table->string('billing_name', 150)->nullable();
            });
        }

        Http::fake([
            '*' => Http::response(['message' => 'Notificación creada y encolada'], 201),
        ]);

        Mail::shouldReceive('html')->times(3)->andReturnNull();

        $orderId = (int) $this->postJson('/api/orders', $this->orderPayload([
            'order_number' => 'ORD-3001',
            'user_id' => '88',
            'subtotal' => 72000,
            'shipping_cost' => 4000,
            'total' => 76000,
            'billing_name' => 'Cliente Prueba',
            'billing_email' => 'cliente.prueba@angelow.test',
            'items' => [
                [
                    'product_id' => 12,
                    'color_variant_id' => 22,
                    'size_variant_id' => 32,
                    'product_name' => 'Camiseta infantil',
                    'variant_name' => 'Blanca / 6T',
                    'price' => 72000,
                    'quantity' => 1,
                    'total' => 72000,
                ],
            ],
        ]))->json('id');

        $this->patchJson("/api/orders/{$orderId}/status", [
            'status' => 'processing',
            'changed_by' => '1',
            'changed_by_name' => 'admin',
            'description' => 'Actualización operativa de estado',
        ])->assertOk();

        $this->patchJson("/api/orders/{$orderId}/payment-status", [
            'payment_status' => 'paid',
            'changed_by' => '1',
            'changed_by_name' => 'admin',
            'description' => 'Pago confirmado',
        ])->assertOk();

        // El flujo actual puede notificar la creación y luego cada cambio operativo.
        Http::assertSentCount(3);
        Http::assertSent(function ($request) use ($orderId): bool {
            return str_contains($request->url(), '/notifications')
                && (string) ($request['related_entity_type'] ?? '') === 'order'
                && (int) ($request['related_entity_id'] ?? 0) === $orderId
                && (string) ($request['user_id'] ?? '') === '88';
        });
    }

    public function test_customer_can_cancel_processing_order_and_trigger_refund_notifications(): void
    {
        if (!Schema::hasColumn('orders', 'billing_email')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('billing_email', 255)->nullable();
                $table->string('billing_name', 150)->nullable();
            });
        }

        config()->set('services.refunds.team_email', 'reembolsos@angelow.test');

        Http::fake([
            '*' => Http::response(['message' => 'Notificación creada y encolada'], 201),
        ]);

        Mail::shouldReceive('html')->times(3)->andReturnNull();

        $orderId = (int) $this->postJson('/api/orders', $this->orderPayload([
            'order_number' => 'ORD-4001',
            'user_id' => '99',
            'subtotal' => 50000,
            'shipping_cost' => 6000,
            'total' => 56000,
            'status' => 'processing',
            'payment_status' => 'paid',
            'billing_name' => 'Cliente Cancelación',
            'billing_email' => 'cliente.cancelacion@angelow.test',
            'items' => [
                [
                    'product_id' => 13,
                    'color_variant_id' => 23,
                    'size_variant_id' => 33,
                    'product_name' => 'Pantalón infantil',
                    'variant_name' => 'Azul / 5T',
                    'price' => 50000,
                    'quantity' => 1,
                    'total' => 50000,
                ],
            ],
        ]))->json('id');

        $this->patchJson("/api/orders/{$orderId}/cancel", [
            'user_id' => '99',
            'user_email' => 'cliente.cancelacion@angelow.test',
            'reason' => 'Cambio de decisión',
            'cancelled_by_name' => 'Cliente Cancelación',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.refund_required', true)
            ->assertJsonPath('data.payment_status', 'pending_refund');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'cancelled',
            'payment_status' => 'pending_refund',
        ]);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $orderId,
            'field_changed' => 'status',
            'new_value' => 'cancelled',
        ]);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $orderId,
            'field_changed' => 'payment_status',
            'new_value' => 'pending_refund',
        ]);

        // La cancelación conserva la notificación al cliente y puede sumar aviso operativo de reembolso.
        Http::assertSentCount(2);
        Http::assertSent(function ($request) use ($orderId): bool {
            return str_contains($request->url(), '/notifications')
                && (string) ($request['related_entity_type'] ?? '') === 'order'
                && (int) ($request['related_entity_id'] ?? 0) === $orderId
                && (string) ($request['user_id'] ?? '') === '99';
        });

        $currentStatus = DB::table('orders')->where('id', $orderId)->value('status');
        $this->assertSame('cancelled', $currentStatus);
    }

    public function test_expired_stock_reservation_cancels_order_and_notifies_customer(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Notificación creada y encolada'], 201),
        ]);

        $orderId = (int) DB::table('orders')->insertGetId([
            'order_number' => 'ORD-RES-TTL',
            'user_id' => '77',
            'status' => 'pending',
            'subtotal' => 41000,
            'total' => 41000,
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reservationService = Mockery::mock(StockReservationService::class);
        $reservationService
            ->shouldReceive('expireReservation')
            ->once()
            ->with($orderId)
            ->andReturn(['ok' => true, 'released' => 1]);

        $realtimePublisher = Mockery::mock(StockReservationRealtimePublisher::class);
        $realtimePublisher
            ->shouldReceive('publish')
            ->once()
            ->with('order.stock_reservation.auto_cancelled', Mockery::on(function (array $payload) use ($orderId): bool {
                return (int) ($payload['order_id'] ?? 0) === $orderId
                    && ($payload['new_status'] ?? null) === 'cancelled'
                    && ($payload['reason'] ?? null) === 'reservation_ttl_expired'
                    && str_contains((string) ($payload['user_instruction'] ?? ''), 'crear un nuevo pedido');
            }));

        // Reutiliza el job real para asegurar que TTL no vuelva a escribir el estado `expired`.
        (new ExpireStockReservationJob($orderId))->handle($reservationService, $realtimePublisher);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $orderId,
            'field_changed' => 'status',
            'new_value' => 'cancelled',
        ]);

        $this->assertDatabaseMissing('order_status_history', [
            'order_id' => $orderId,
            'new_value' => 'expired',
        ]);

        Http::assertSent(function ($request) use ($orderId): bool {
            return str_contains($request->url(), '/notifications')
                && (string) ($request['user_id'] ?? '') === '77'
                && (int) ($request['related_entity_id'] ?? 0) === $orderId
                && str_contains((string) ($request['message'] ?? ''), 'crear un nuevo pedido');
        });
    }

    public function test_order_rejects_discount_that_would_create_zero_total(): void
    {
        $this->postJson('/api/orders', $this->orderPayload([
            'order_number' => 'ORD-ZERO-TOTAL',
            'subtotal' => 4000,
            'shipping_cost' => 0,
            'discount_amount' => 25000,
            'total' => 0,
            'items' => [[
                'product_id' => 71,
                'color_variant_id' => 20,
                'size_variant_id' => 30,
                'product_name' => 'Ropa deportiva',
                'variant_name' => 'Negro / M',
                'price' => 4000,
                'quantity' => 1,
                'total' => 4000,
            ]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total']);

        $this->assertDatabaseMissing('orders', ['order_number' => 'ORD-ZERO-TOTAL']);
    }

    public function test_verified_payment_retries_shipping_publication_with_method_snapshot(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Operación aceptada'], 201),
        ]);
        Mail::fake();

        $createResponse = $this->postJson('/api/orders', $this->orderPayload([
            'order_number' => 'ORD-SHIP-SNAPSHOT',
            'shipping_method_id' => null,
            'shipping_method_name' => 'Envío estándar',
            'shipping_delivery_time' => 'Coordinaremos el despacho contigo',
            'shipping_address' => 'Carrera 67 # 10-20',
            'shipping_city' => 'Medellín',
            'shipping_address_id' => 73,
            'payment_status' => 'pending',
        ]));
        $createResponse->assertCreated();
        $orderId = (int) $createResponse->json('id');

        $payload = ['payment_status' => 'verified'];
        $this->patchJson("/api/orders/{$orderId}/payment-status", $payload)->assertOk();
        $this->patchJson("/api/orders/{$orderId}/payment-status", $payload)->assertOk();

        $shippingRequests = collect(Http::recorded())
            ->map(static fn (array $record) => $record[0])
            ->filter(static fn ($request): bool => str_contains($request->url(), '/internal/deliveries/eligible'))
            ->values();

        $this->assertCount(2, $shippingRequests);
        $this->assertSame('Envío estándar', $shippingRequests->first()['shipping_method_name']);
        $this->assertNull($shippingRequests->first()['shipping_method_id']);
        $this->assertSame(73, $shippingRequests->first()['shipping_address_id']);
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'shipping_address_id' => 73,
        ]);
    }
}
