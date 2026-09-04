<?php

namespace Tests\Feature;

use App\Models\CourierDocument;
use App\Models\CourierProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourierProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'courier-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('courier_token_' . hash('sha256', self::TOKEN), [
            'id' => 'courier-1',
            'email' => 'courier@example.com',
            'role' => 'courier',
        ]);
    }

    public function test_obsolete_courier_profile_columns_are_removed_by_migrations(): void
    {
        foreach ([
            'city',
            'emergency_contact_name',
            'emergency_contact_phone',
            'work_modality',
            'eps_name',
            'pension_fund',
            'arl_status',
            'data_authorization_at',
        ] as $column) {
            $this->assertFalse(Schema::hasColumn('courier_profiles', $column), $column);
        }

        $this->assertTrue(Schema::hasColumn('courier_profiles', 'terms_accepted_at'));
    }

    public function test_pruning_migration_restores_structure_and_can_be_reapplied(): void
    {
        $migration = require database_path(
            'migrations/2026_07_25_000001_remove_unused_courier_profile_fields.php',
        );

        $migration->down();
        foreach ([
            'city',
            'emergency_contact_name',
            'emergency_contact_phone',
            'work_modality',
            'eps_name',
            'pension_fund',
            'arl_status',
            'data_authorization_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('courier_profiles', $column), $column);
        }

        $migration->up();
        $this->assertFalse(Schema::hasColumn('courier_profiles', 'city'));
        $this->assertFalse(Schema::hasColumn('courier_profiles', 'data_authorization_at'));
    }

    public function test_profile_endpoint_rejects_removed_and_invalid_fields(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/courier/profile', [
                'document_type' => 'cc',
                'document_number' => '123',
                'birth_date' => now()->subYears(12)->toDateString(),
                'phone' => '12345',
                'address' => 'x',
                'city' => 'Medellín',
                'emergency_contact_name' => 'Persona',
                'work_modality' => 'independent',
                'eps_name' => 'EPS',
                'accept_data_policy' => true,
                'terms_version' => '2026-07-25',
                'accept_terms' => false,
                'vehicle' => ['type' => 'foot'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'document_number',
                'birth_date',
                'phone',
                'address',
                'city',
                'emergency_contact_name',
                'work_modality',
                'eps_name',
                'accept_data_policy',
                'accept_terms',
            ]);
    }

    public function test_non_motorized_profile_requires_identity_documents_but_not_social_security(): void
    {
        $response = $this->withToken(self::TOKEN)
            ->postJson('/api/courier/profile', $this->validPayload());

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Adjunta todos los documentos requeridos.')
            ->assertJsonPath('missing_documents', [
                'identity_front',
                'identity_back',
                'profile_photo',
            ]);

        $this->assertNotContains('social_security', $response->json('missing_documents'));
    }

    public function test_motorized_profile_requires_license_and_property_card(): void
    {
        $payload = $this->validPayload();
        $payload['vehicle'] = [
            'type' => 'motorcycle',
            'make_id' => '80',
            'make_name' => 'Honda',
            'model_id' => '125',
            'model_name' => 'CB 125',
            'color_name' => 'Negro',
            'color_hex' => '#000000',
            'year' => 2024,
            'plate' => 'ABC12D',
            'ownership_type' => 'owned',
        ];
        $payload['technical_inspection_not_applicable'] = true;

        $response = $this->withToken(self::TOKEN)
            ->postJson('/api/courier/profile', $payload);

        $response->assertUnprocessable();
        $missing = $response->json('missing_documents');
        $this->assertContains('driving_license', $missing);
        $this->assertContains('vehicle_registration', $missing);
        $this->assertContains('soat', $missing);
        $this->assertNotContains('technical_inspection', $missing);
        $this->assertNotContains('social_security', $missing);
    }

    public function test_rejected_document_must_be_replaced_before_resubmitting(): void
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
            'status' => 'rejected',
            'rejection_reason' => 'Reemplaza el frente del documento.',
            'is_active' => false,
            'terms_version' => '2026-07-25',
            'terms_accepted_at' => now(),
        ]);
        foreach (['identity_front', 'identity_back', 'profile_photo'] as $type) {
            CourierDocument::query()->create([
                'courier_profile_id' => $profile->id,
                'type' => $type,
                'path' => "uploads/couriers/{$profile->id}/{$type}.jpg",
                'status' => $type === 'identity_front' ? 'rejected' : 'approved',
                'review_note' => $type === 'identity_front' ? 'La imagen no es legible.' : null,
            ]);
        }

        $this->withToken(self::TOKEN)
            ->postJson('/api/courier/profile', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonPath('missing_documents', ['identity_front']);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'document_type' => 'cc',
            'document_number' => '1030123456',
            'birth_date' => now()->subYears(25)->toDateString(),
            'phone' => '3001234567',
            'address' => 'Carrera 43 A 10 20',
            'terms_version' => '2026-07-25',
            'accept_terms' => true,
            'vehicle' => ['type' => 'foot'],
            'technical_inspection_not_applicable' => false,
        ];
    }
}
