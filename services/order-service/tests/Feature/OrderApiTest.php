<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
