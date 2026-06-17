<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Clase base abstracta para todos los tests del auth-service.
 *
 * Extiende el TestCase de Laravel para mantener consistencia
 * en la configuración de pruebas del servicio. Las clases
 * hijas (Feature\*, Unit\*) heredan automáticamente el
 * bootstrapping de la aplicación.
 */
abstract class TestCase extends BaseTestCase
{
    //
}
