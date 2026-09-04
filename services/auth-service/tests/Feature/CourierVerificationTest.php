<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RegistrationVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourierVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_unknown_email_continues_to_registration(): void
    {
        $result = $this->verify('nuevo@example.com');

        $this->assertSame('register', $result['next_step']);
        $this->assertNotEmpty($result['verification_token']);
    }

    public function test_verified_courier_continues_to_password(): void
    {
        User::query()->create([
            'id' => 'courier-1',
            'name' => 'Repartidor Prueba',
            'email' => 'courier@example.com',
            'password' => 'secret123',
            'role' => 'courier',
        ]);

        $result = $this->verify('courier@example.com');

        $this->assertSame('password', $result['next_step']);
    }

    public function test_verified_non_courier_is_blocked_from_mobile_app(): void
    {
        User::query()->create([
            'id' => 'customer-1',
            'name' => 'Cliente Prueba',
            'email' => 'cliente@example.com',
            'password' => 'secret123',
            'role' => 'customer',
        ]);

        $result = $this->verify('cliente@example.com');

        $this->assertSame('blocked', $result['next_step']);
        $this->assertStringContainsString('angelow.online', $result['message']);
    }

    public function test_internal_profiles_can_filter_administrators_for_operational_alerts(): void
    {
        config(['services.internal.api_token' => 'test-internal-token']);
        User::query()->create([
            'id' => 'admin-1', 'name' => 'Administradora', 'email' => 'admin@example.com',
            'password' => 'secret123', 'role' => 'admin',
        ]);
        User::query()->create([
            'id' => 'customer-2', 'name' => 'Cliente', 'email' => 'customer@example.com',
            'password' => 'secret123', 'role' => 'customer',
        ]);

        $this->withHeader('X-Internal-Token', 'test-internal-token')
            ->getJson('/api/internal/users/profiles?role=admin')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'admin-1')
            ->assertJsonPath('data.0.role', 'admin');
    }

    public function test_courier_registration_rejects_invalid_identity_data_on_server(): void
    {
        $this->postJson('/api/auth/courier/register', [
            'name' => 'Leidy',
            'email' => 'correo-invalido',
            'phone' => '12345',
            'password' => 'soloclave',
            'password_confirmation' => 'otra-clave',
            'registration_token' => 'token-corto',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'phone',
                'password',
                'registration_token',
            ]);
    }

    public function test_courier_login_rejects_an_invalid_password_shape_before_authentication(): void
    {
        $this->postJson('/api/auth/courier/login', [
            'email' => 'courier@example.com',
            'password' => '123',
            'verification_token' => str_repeat('a', 64),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    /** @return array<string, mixed> */
    private function verify(string $email): array
    {
        Cache::put('courier_verification:code:' . sha1($email), [
            'hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5)->toISOString(),
        ], now()->addMinutes(5));

        return app(RegistrationVerificationService::class)->verifyCourierCode($email, '123456');
    }
}
