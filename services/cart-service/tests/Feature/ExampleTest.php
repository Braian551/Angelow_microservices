<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Prueba de ejemplo del cart-service (Feature).
 * Verifica que el endpoint de salud responda correctamente.
 */
class ExampleTest extends TestCase
{
    /**
     * Verifica que GET /api/health retorne service=cart-service y status=ok.
     */
    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('service', 'cart-service')
            ->assertJsonPath('status', 'ok');
    }
}
