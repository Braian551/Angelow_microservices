<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Sembrador principal de la base de datos del audit-service.
 *
 * Poblador inicial para entornos de desarrollo/pruebas.
 * Actualmente solo crea un usuario de prueba; en el futuro
 * puede incluir registros de auditoría de ejemplo para
 * verificar el funcionamiento de los endpoints.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta los sembradores de la base de datos.
     *
     * Crea un usuario de prueba por defecto y, si se descomenta
     * la línea superior, genera 10 usuarios adicionales con Factory.
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
