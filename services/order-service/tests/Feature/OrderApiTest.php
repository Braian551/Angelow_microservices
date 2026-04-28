<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_view_order(): void
    {
        $createResponse = $this->postJson('/api/orders', [
            'order_number' => 'ORD-1001',
            'user_id' => '12',
            'subtotal' => 100000,
            'total' => 112000,
        ]);

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
        $orderId = (int) $this->postJson('/api/orders', [
            'order_number' => 'ORD-2001',
            'user_id' => '50',
            'subtotal' => 85000,
            'total' => 90000,
        ])->json('id');

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

        Mail::shouldReceive('html')->twice()->andReturnNull();

        $orderId = (int) $this->postJson('/api/orders', [
            'order_number' => 'ORD-3001',
            'user_id' => '88',
            'subtotal' => 72000,
            'total' => 76000,
            'billing_name' => 'Cliente Prueba',
            'billing_email' => 'cliente.prueba@angelow.test',
        ])->json('id');

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

        Http::assertSentCount(2);
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

        Mail::shouldReceive('html')->twice()->andReturnNull();

        $orderId = (int) $this->postJson('/api/orders', [
            'order_number' => 'ORD-4001',
            'user_id' => '99',
            'subtotal' => 50000,
            'total' => 56000,
            'status' => 'processing',
            'payment_status' => 'paid',
            'billing_name' => 'Cliente Cancelación',
            'billing_email' => 'cliente.cancelacion@angelow.test',
        ])->json('id');

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

        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($orderId): bool {
            return str_contains($request->url(), '/notifications')
                && (string) ($request['related_entity_type'] ?? '') === 'order'
                && (int) ($request['related_entity_id'] ?? 0) === $orderId
                && (string) ($request['user_id'] ?? '') === '99';
        });

        $currentStatus = DB::table('orders')->where('id', $orderId)->value('status');
        $this->assertSame('cancelled', $currentStatus);
    }
}
