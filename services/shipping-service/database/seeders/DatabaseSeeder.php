<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Sembrador de datos iniciales para shipping-service.
 *
 * Crea datos de prueba para desarrollo y verificación del servicio.
 * Actualmente solo crea un usuario de prueba. En el futuro puede
 * poblarse con métodos de envío, reglas de precio y direcciones
 * de ejemplo para facilitar el desarrollo local.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta los sembradores de datos.
     *
     * Crea un usuario de prueba con datos fijos para verificar
     * el funcionamiento básico del servicio en entorno local.
     */
    public function run(): void
    {
        // Ejemplo: crear 10 usuarios con datos aleatorios
        // User::factory(10)->create();

        // Usuario de prueba con datos conocidos
        User::factory()->create([
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com',
        ]);
    }
}
