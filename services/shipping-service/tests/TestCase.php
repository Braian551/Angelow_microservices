<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Clase base abstracta para todos los tests de shipping-service.
 *
 * Extiende TestCase de Laravel para proporcionar funcionalidades
 * comunes como:
 * - RefreshDatabase para limpiar datos entre tests
 * - Métodos helper para peticiones HTTP a la API
 * - Integración con el contenedor de Laravel para pruebas
 *
 * Las clases concretas (ExampleTest, ShippingApiTest) heredan
 * de esta clase para ejecutar sus escenarios de prueba.
 */
abstract class TestCase extends BaseTestCase
{
    //
}
