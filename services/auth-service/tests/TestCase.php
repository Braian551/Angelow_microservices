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
    /**
     * Fuerza las pruebas a usar SQLite en memoria aunque el contenedor
     * tenga las variables de PostgreSQL de desarrollo.
     *
     * Sin esta protección, RefreshDatabase podría ejecutar migrate:fresh
     * sobre angelow_auth y borrar cuentas reales del entorno local.
     */
    public function createApplication()
    {
        $app = parent::createApplication();
        $app->make('config')->set('database.default', 'sqlite');
        $app->make('config')->set('database.connections.sqlite.database', ':memory:');
        $app->make('db')->purge('sqlite');

        return $app;
    }
}
