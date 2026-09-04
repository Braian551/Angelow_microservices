<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_read_and_update_customer_identity_role_and_status(): void
    {
        $admin = $this->user('admin-1', 'admin@example.com', 'admin');
        $customer = $this->user('customer-1', 'customer@example.com', 'customer');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users/customer-1')
            ->assertOk()
            ->assertJsonPath('data.role', 'customer')
            ->assertJsonPath('data.active', true);

        $this->putJson('/api/admin/users/customer-1', [
            'name' => 'Cliente Editado',
            'email' => 'editado@example.com',
            'phone' => '3001234567',
            'role' => 'courier',
            'active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.role', 'courier')
            ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Cliente Editado',
            'email' => 'editado@example.com',
            'phone' => '3001234567',
            'role' => 'courier',
            'is_blocked' => true,
        ]);
    }

    public function test_administrator_cannot_remove_own_administrative_access(): void
    {
        $admin = $this->user('admin-1', 'admin@example.com', 'admin');
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/users/admin-1', ['role' => 'customer'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes cambiar tu propio rol de administrador.');

        $this->putJson('/api/admin/users/admin-1', ['active' => false])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes desactivar tu propia cuenta.');
    }

    public function test_unauthenticated_api_request_returns_json_401_without_an_accept_header(): void
    {
        $this->get('/api/admin/users/customer-1')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    private function user(string $id, string $email, string $role): User
    {
        return User::query()->create([
            'id' => $id,
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'secret123',
            'role' => $role,
        ]);
    }
}
