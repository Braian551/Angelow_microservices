<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_endpoint_returns_audit_records(): void
    {
        DB::table('audit_orders')->insert([
            'orden_id' => 10,
            'accion' => 'UPDATE',
            'usuario_id' => '1',
            'sql_usuario' => 'admin@example.com',
            'fecha' => now(),
            'detalles' => 'Cambio de estado',
            'trial548' => null,
        ]);

        $this->getJson('/api/audits/orders')
            ->assertOk()
            ->assertJsonPath('data.0.orden_id', 10)
            ->assertJsonPath('data.0.accion', 'UPDATE');
    }

    public function test_users_and_products_endpoints_return_records(): void
    {
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

        $this->getJson('/api/audits/users')
            ->assertOk()
            ->assertJsonPath('data.0.usuario_id', '2');

        $this->getJson('/api/audits/products')
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Camiseta Blanca');
    }
}
