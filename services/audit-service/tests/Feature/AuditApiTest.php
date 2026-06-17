<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pruebas de integración para los endpoints de auditoría.
 *
 * Verifica que cada endpoint del AuditController devuelva
 * los registros correctamente cuando existen datos en la BD,
 * usando RefreshDatabase para aislar cada prueba.
 */
class AuditApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba que el endpoint /api/audits/orders devuelva
     * el registro insertado con los campos esperados.
     */
    public function test_orders_endpoint_returns_audit_records(): void
    {
        // Inserta un registro de auditoría de prueba en la tabla audit_orders
        DB::table('audit_orders')->insert([
            'orden_id' => 10,
            'accion' => 'UPDATE',
            'usuario_id' => '1',
            'sql_usuario' => 'admin@example.com',
            'fecha' => now(),
            'detalles' => 'Cambio de estado',
            'trial548' => null,
        ]);

        // Consulta el endpoint y verifica que devuelva el registro correcto
        $this->getJson('/api/audits/orders')
            ->assertOk()
            ->assertJsonPath('data.0.orden_id', 10)
            ->assertJsonPath('data.0.accion', 'UPDATE');
    }

    /**
     * Prueba que los endpoints de usuarios y productos devuelvan
     * los registros insertados correctamente.
     */
    public function test_users_and_products_endpoints_return_records(): void
    {
        // Inserta registros de prueba en ambas tablas
        DB::table('audit_users')->insert([
            'usuario_id' => '2',
            'accion' => 'INSERT',
            'usuario_modificador' => '1',
            'sql_usuario' => 'admin@example.com',
            'fecha' => now(),
            'detalles' => 'Creacion de usuario',
            'trial548' => null,
        ]);

        DB::table('productos_auditoria')->insert([
            'nombre' => 'Camiseta Blanca',
            'accion' => 'Creado',
            'created_at' => now(),
            'updated_at' => now(),
            'trial554' => null,
        ]);

        // Verifica endpoint de usuarios
        $this->getJson('/api/audits/users')
            ->assertOk()
            ->assertJsonPath('data.0.usuario_id', '2');

        // Verifica endpoint de productos
        $this->getJson('/api/audits/products')
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Camiseta Blanca');
    }
}
