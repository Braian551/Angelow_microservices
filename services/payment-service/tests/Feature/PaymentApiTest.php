<?php

namespace Tests\Feature;

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
}
