<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Prueba de ejemplo para el endpoint de health check del auth-service.
 */
class ExampleTest extends TestCase
{
    /**
     * Verifica que el health check retorne código 200,
     * el nombre del servicio y estado "ok".
     */
    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('service', 'auth-service')
            ->assertJsonPath('status', 'ok');
    }
}
