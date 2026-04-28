<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_only_active_banks(): void
    {
        DB::table('colombian_banks')->insert([
            [
                'bank_code' => '001',
                'bank_name' => 'Banco Activo',
                'is_active' => true,
                'trial551' => null,
            ],
            [
                'bank_code' => '999',
                'bank_name' => 'Banco Inactivo',
                'is_active' => false,
                'trial551' => null,
            ],
        ]);

        $this->getJson('/api/banks')
            ->assertOk()
            ->assertJsonPath('data.0.bank_name', 'Banco Activo')
            ->assertJsonMissing(['bank_code' => '999']);
    }

    public function test_can_get_active_payment_account_configuration(): void
    {
        DB::table('colombian_banks')->insert([
            'bank_code' => '031',
            'bank_name' => 'Bancolombia',
            'is_active' => true,
            'trial551' => null,
        ]);

        DB::table('bank_account_config')->insert([
            [
                'bank_code' => '031',
                'account_number' => '000111222',
                'account_type' => 'ahorros',
                'account_holder' => 'Cuenta Inactiva',
                'identification_type' => 'cc',
                'identification_number' => '1111111111',
                'email' => 'inactiva@angelow.test',
                'phone' => '3000000000',
                'is_active' => false,
                'created_by' => '1',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
                'trial548' => null,
            ],
            [
                'bank_code' => '031',
                'account_number' => '1234567890',
                'account_type' => 'corriente',
                'account_holder' => 'Braian Oquendo',
                'identification_type' => 'nit',
                'identification_number' => '900123456',
                'email' => 'pagos@angelow.test',
                'phone' => '3013636902',
                'is_active' => true,
                'created_by' => '1',
                'created_at' => now(),
                'updated_at' => now(),
                'trial548' => null,
            ],
        ]);

        $this->getJson('/api/payment-account')
            ->assertOk()
            ->assertJsonPath('data.bank_name', 'Bancolombia')
            ->assertJsonPath('data.account_number', '1234567890')
            ->assertJsonPath('data.account_holder', 'Braian Oquendo')
            ->assertJsonPath('data.account_type_label', 'Cuenta corriente')
            ->assertJsonPath('data.identification_type_label', 'NIT');
    }

    public function test_can_create_and_verify_payment_transaction(): void
    {
        $createResponse = $this->postJson('/api/payments', [
            'order_id' => 100,
            'user_id' => '20',
            'amount' => 99000,
            'reference_number' => 'REF-001',
        ]);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('message', 'Transaccion creada');

        $paymentId = (int) $createResponse->json('id');

        $this->patchJson("/api/payments/{$paymentId}/verify", [
            'status' => 'approved',
            'admin_notes' => 'Verificado por pruebas',
            'verified_by' => '1',
        ])->assertOk()->assertJsonPath('message', 'Transaccion actualizada');

        $this->getJson('/api/payments?status=approved')
            ->assertOk()
            ->assertJsonPath('data.0.id', $paymentId)
            ->assertJsonPath('data.0.status', 'approved');
    }

    public function test_admin_can_get_payment_account_settings_payload(): void
    {
        $this->withoutMiddleware(EnsureAdmin::class);

        DB::table('colombian_banks')->insert([
            [
                'bank_code' => '031',
                'bank_name' => 'Bancolombia',
                'is_active' => true,
                'trial551' => null,
            ],
            [
                'bank_code' => '040',
                'bank_name' => 'Banco Agrario',
                'is_active' => true,
                'trial551' => null,
            ],
        ]);

        DB::table('bank_account_config')->insert([
            'bank_code' => '031',
            'account_number' => '1234567890',
            'account_type' => 'ahorros',
            'account_holder' => 'Cuenta Prueba',
            'identification_type' => 'cc',
            'identification_number' => '12345678',
            'email' => 'pagos@angelow.test',
            'phone' => '3001112233',
            'is_active' => true,
            'created_by' => '1',
            'created_at' => now(),
            'updated_at' => now(),
            'trial548' => null,
        ]);

        $this->getJson('/api/admin/payment-account')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account.bank_code', '031')
            ->assertJsonCount(2, 'data.banks');
    }

    public function test_admin_can_save_payment_account_settings(): void
    {
        $this->withoutMiddleware(EnsureAdmin::class);

        DB::table('colombian_banks')->insert([
            'bank_code' => '031',
            'bank_name' => 'Bancolombia',
            'is_active' => true,
            'trial551' => null,
        ]);

        $response = $this->putJson('/api/admin/payment-account', [
            'bank_code' => '031',
            'account_number' => '987654321',
            'account_type' => 'corriente',
            'account_holder' => 'Braian Oquendo',
            'identification_type' => 'nit',
            'identification_number' => '900123456',
            'email' => 'cuentas@angelow.test',
            'phone' => '3013636902',
            'is_active' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bank_code', '031')
            ->assertJsonPath('data.account_holder', 'Braian Oquendo');

        $this->assertDatabaseHas('bank_account_config', [
            'bank_code' => '031',
            'account_number' => '987654321',
            'account_holder' => 'Braian Oquendo',
            'is_active' => true,
        ]);
    }

    public function test_admin_payments_list_normalizes_status_values(): void
    {
        $this->withoutMiddleware(EnsureAdmin::class);

        DB::table('payment_transactions')->insert([
            [
                'order_id' => 1001,
                'user_id' => '11',
                'amount' => 88000,
                'status' => 'verified',
                'created_at' => now()->subMinutes(2),
                'updated_at' => now()->subMinutes(2),
                'trial554' => null,
            ],
            [
                'order_id' => 1002,
                'user_id' => '12',
                'amount' => 98000,
                'status' => '',
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
                'trial554' => null,
            ],
        ]);

        $response = $this->getJson('/api/admin/payments');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.1.status', 'approved');
    }

    public function test_admin_can_verify_payment_with_approved_status(): void
    {
        $this->withoutMiddleware(EnsureAdmin::class);

        $paymentId = (int) DB::table('payment_transactions')->insertGetId([
            'order_id' => 2001,
            'user_id' => '30',
            'amount' => 125000,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
            'trial554' => null,
        ]);

        $this->patchJson("/api/admin/payments/{$paymentId}", [
            'status' => 'approved',
            'admin_notes' => 'Verificado en pruebas',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $paymentId,
            'status' => 'approved',
        ]);
    }
}
