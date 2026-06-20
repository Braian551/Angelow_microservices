<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Sembrador principal de la base de datos del auth-service.
 *
 * Poblador inicial para entornos de desarrollo/pruebas.
 * Actualmente solo crea un usuario de prueba; expandir
 * según necesidades del equipo.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta los sembradores de la base de datos.
     *
     * Crea un usuario de prueba por defecto. Para generar
     * más usuarios, descomentar User::factory(10)->create().
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Usuario de prueba',
            'email' => 'test@example.com',
        ]);
    }
}
