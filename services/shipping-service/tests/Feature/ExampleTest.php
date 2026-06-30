<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Test de funcionalidades básicas del shipping-service.
 *
 * Verifica que los endpoints fundamentales respondan correctamente.
 * Sirve como prueba de humo antes de ejecutar tests específicos.
 */
class ExampleTest extends TestCase
{
    /**
     * Test: El endpoint de health check retorna estado OK.
     * Verifica que el servicio esté operativo y responda con
     * la estructura JSON esperada (service + status + timestamp).
     */
    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('service', 'shipping-service')
            ->assertJsonPath('status', 'ok');
    }
}
