<?php

namespace Tests\Feature;

use App\Jobs\DispatchNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_dispatches_job_and_creates_queue_record(): void
    {
        Queue::fake();

        $userId = 'u' . (string) random_int(100000, 999999);

        $typeId = DB::table('notification_types')->insertGetId([
            'name' => 'Pedido',
            'description' => 'Evento de pedido',
            'template' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'trial551' => null,
        ]);

        $response = $this->postJson('/api/notifications', [
            'user_id' => $userId,
            'type_id' => $typeId,
            'title' => 'Pedido confirmado',
            'message' => 'Tu pedido fue registrado',
            'related_entity_type' => 'order',
            'related_entity_id' => 1,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('message', 'Notificacion creada y encolada');

        $notificationId = (int) $response->json('id');

        Queue::assertPushed(DispatchNotificationJob::class, function (DispatchNotificationJob $job) use ($notificationId, $userId): bool {
            return $job->notificationId === $notificationId && $job->userId === $userId;
        });

        $this->assertDatabaseHas('notification_queue', [
            'notification_id' => $notificationId,
            'channel' => 'push',
            'status' => 'pending',
        ]);
    }

    public function test_can_list_and_mark_notification_as_read(): void
    {
        $typeId = DB::table('notification_types')->insertGetId([
            'name' => 'Cuenta',
            'description' => 'Eventos de cuenta',
            'template' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'trial551' => null,
        ]);

        $notificationId = DB::table('notifications')->insertGetId([
            'user_id' => '77',
            'type_id' => $typeId,
            'title' => 'Bienvenido',
            'message' => 'Cuenta creada',
            'related_entity_type' => null,
            'related_entity_id' => null,
            'is_read' => false,
            'is_email_sent' => false,
            'is_sms_sent' => false,
            'is_push_sent' => false,
            'expires_at' => null,
            'created_at' => now(),
            'read_at' => null,
            'trial551' => null,
        ]);

        $this->getJson('/api/notifications?user_id=77')
            ->assertOk()
            ->assertJsonPath('data.0.id', $notificationId);

        $this->patchJson("/api/notifications/{$notificationId}/read")
            ->assertOk()
            ->assertJsonPath('message', 'Notificacion marcada como leida');

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'is_read' => true,
        ]);
    }
}