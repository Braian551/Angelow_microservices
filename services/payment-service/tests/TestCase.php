<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Aísla las pruebas de la base PostgreSQL de desarrollo aunque Docker
     * inyecte sus variables de conexión al contenedor.
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
