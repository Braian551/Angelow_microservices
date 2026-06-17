<?php

namespace Tests\Feature;

// Comentario de mantenimiento: Esta prueba protege el comportamiento esperado del servicio.

use Tests\TestCase;

/**
 * Esta prueba protege el comportamiento esperado del servicio.
 */
class ExampleTest extends TestCase
{
    /**
     * Explica la intención de test_health_endpoint_returns_ok dentro del flujo del servicio.
     */
    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('service', 'catalog-service')
            ->assertJsonPath('status', 'ok');
    }
}
