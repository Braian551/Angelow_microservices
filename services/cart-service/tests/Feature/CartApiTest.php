<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_return_empty_cart_for_a_session(): void
    {
        Http::fake();

        $this->getJson('/api/cart?session_id=sess_test')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.item_count', 0)
            ->assertJsonPath('data.subtotal', 0);
    }

    public function test_can_add_item_and_read_cart_with_catalog_data(): void
    {
        Http::fake([
            '*/internal/variants/10' => Http::response([
                'data' => [
                    'id' => 10,
                    'product_id' => 100,
                    'color_variant_id' => 20,
                    'price' => 90000,
                    'compare_price' => 95000,
                    'quantity' => 25,
                    'size_name' => '8',
                    'color_name' => 'Azul',
                    'color_hex' => '#0000ff',
                ],
            ], 200),
            '*/internal/products/100' => Http::response([
                'data' => [
                    'id' => 100,
                    'name' => 'Conjunto Fiesta',
                    'slug' => 'conjunto-fiesta',
                    'primary_image' => 'uploads/productos/conjunto-fiesta.webp',
                ],
            ], 200),
        ]);

        $this->postJson('/api/cart/add', [
            'session_id' => 'sess_test',
            'product_id' => 100,
            'color_variant_id' => 20,
            'size_variant_id' => 10,
            'quantity' => 2,
        ])->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson('/api/cart?session_id=sess_test')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.item_count', 1)
            ->assertJsonPath('data.items.0.product_name', 'Conjunto Fiesta')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.subtotal', 180000);
    }

    public function test_update_quantity_fails_if_new_quantity_exceeds_stock(): void
    {
        Http::fake([
            '*/internal/variants/10' => Http::sequence()
                ->push([
                    'data' => [
                        'id' => 10,
                        'product_id' => 100,
                        'color_variant_id' => 20,
                        'price' => 90000,
                        'compare_price' => 95000,
                        'quantity' => 2,
                        'size_name' => '8',
                        'color_name' => 'Azul',
                        'color_hex' => '#0000ff',
                    ],
                ], 200)
                ->push([
                    'data' => [
                        'id' => 10,
                        'product_id' => 100,
                        'color_variant_id' => 20,
                        'price' => 90000,
                        'compare_price' => 95000,
                        'quantity' => 2,
                        'size_name' => '8',
                        'color_name' => 'Azul',
                        'color_hex' => '#0000ff',
                    ],
                ], 200),
        ]);

        $this->postJson('/api/cart/add', [
            'session_id' => 'sess_test',
            'product_id' => 100,
            'color_variant_id' => 20,
            'size_variant_id' => 10,
            'quantity' => 1,
        ])->assertStatus(200);

        $itemId = (int) \DB::table('cart_items')->value('id');

        $this->putJson("/api/cart/{$itemId}", ['quantity' => 3])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
