<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Prueba unitaria de ejemplo para verificar la configuración
 * básica de PHPUnit en el servicio de auditoría.
 */
class ExampleTest extends TestCase
{
    /**
     * Prueba básica que siempre pasa.
     * Sirve como verification de que el entorno de pruebas
     * está correctamente configurado.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
