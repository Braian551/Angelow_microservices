<?php

namespace Tests\Feature;

use App\Models\CourierDocument;
use App\Models\CourierProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminCourierReviewTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'admin-courier-review-token';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('admin_token_' . hash('sha256', self::TOKEN), [
            'id' => 'admin-1',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        Http::fake();
    }

    public function test_approval_needs_no_reason_and_approves_all_documents(): void
    {
        [$profile, $document] = $this->pendingApplication();

        $this->withToken(self::TOKEN)
            ->putJson("/api/admin/couriers/{$profile->id}", [
                'status' => 'approved',
                'rejection_reason' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.rejection_reason', null);

        $this->assertDatabaseHas('courier_documents', [
            'id' => $document->id,
            'status' => 'approved',
            'review_note' => null,
        ]);
    }

    public function test_change_request_requires_document_and_specific_instructions(): void
    {
        [$profile, $document] = $this->pendingApplication();

        $this->withToken(self::TOKEN)
            ->putJson("/api/admin/couriers/{$profile->id}", [
                'status' => 'rejected',
                'rejection_reason' => 'Actualiza los adjuntos.',
                'documents' => [
                    $document->id => ['status' => 'approved', 'review_note' => null],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Selecciona al menos un documento que requiera cambios.');

        $this->withToken(self::TOKEN)
            ->putJson("/api/admin/couriers/{$profile->id}", [
                'status' => 'rejected',
                'rejection_reason' => 'Actualiza los adjuntos.',
                'documents' => [
                    $document->id => ['status' => 'rejected', 'review_note' => null],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Indica el cambio requerido en cada documento seleccionado.');

        $this->withToken(self::TOKEN)
            ->putJson("/api/admin/couriers/{$profile->id}", [
                'status' => 'rejected',
                'rejection_reason' => 'Actualiza los adjuntos.',
                'documents' => [
                    $document->id => [
                        'status' => 'rejected',
                        'review_note' => 'La imagen está borrosa; adjunta una foto legible.',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('courier_documents', [
            'id' => $document->id,
            'status' => 'rejected',
            'review_note' => 'La imagen está borrosa; adjunta una foto legible.',
        ]);
    }

    public function test_administrator_can_update_courier_contact_projection(): void
    {
        [$profile] = $this->pendingApplication();

        $this->withToken(self::TOKEN)
            ->putJson("/api/admin/couriers/{$profile->id}", [
                'email' => 'correo.actualizado@example.com',
                'phone' => '3012345678',
                'address' => 'Calle 10 # 20-30',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'correo.actualizado@example.com')
            ->assertJsonPath('data.phone', '3012345678')
            ->assertJsonPath('data.address', 'Calle 10 # 20-30');

        $this->assertDatabaseHas('courier_profiles', [
            'id' => $profile->id,
            'email' => 'correo.actualizado@example.com',
            'phone' => '3012345678',
            'address' => 'Calle 10 # 20-30',
        ]);
    }

    /** @return array{CourierProfile, CourierDocument} */
    private function pendingApplication(): array
    {
        $profile = CourierProfile::query()->create([
            'user_id' => 'courier-1',
            'email' => 'courier@example.com',
            'document_type' => 'cc',
            'document_number' => '1030123456',
            'document_number_hash' => hash('sha256', 'cc:1030123456'),
            'birth_date' => '2000-01-01',
            'phone' => '3001234567',
            'address' => 'Carrera 43 A 10 20',
            'status' => 'pending',
            'is_active' => true,
            'terms_version' => '2026-07-25',
            'terms_accepted_at' => now(),
        ]);
        $document = CourierDocument::query()->create([
            'courier_profile_id' => $profile->id,
            'type' => 'identity_front',
            'path' => "uploads/couriers/{$profile->id}/identity-front.jpg",
            'status' => 'pending',
        ]);

        return [$profile, $document];
    }
}
