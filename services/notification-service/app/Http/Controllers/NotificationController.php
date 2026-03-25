<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchNotificationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'string', 'max:20'],
        ]);

        $items = DB::table('notifications')
            ->where('user_id', $data['user_id'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'string', 'max:20'],
            'type_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string'],
            'related_entity_type' => ['nullable', 'string', 'max:30'],
            'related_entity_id' => ['nullable', 'integer'],
        ]);

        $id = DB::table('notifications')->insertGetId([
            'user_id' => $data['user_id'],
            'type_id' => $data['type_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'related_entity_id' => $data['related_entity_id'] ?? null,
            'is_read' => false,
            'is_email_sent' => false,
            'is_sms_sent' => false,
            'is_push_sent' => false,
            'created_at' => now(),
        ]);

        $payload = [
            'id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'type_id' => $data['type_id'],
        ];

        DispatchNotificationJob::dispatch($id, $data['user_id'], $payload);

        DB::table('notification_queue')->insert([
            'notification_id' => $id,
            'channel' => 'push',
            'status' => 'pending',
            'attempts' => 0,
            'scheduled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notificacion creada y encolada',
            'id' => $id,
        ], 201);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $updated = DB::table('notifications')->where('id', $id)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        if (!$updated) {
            return response()->json(['message' => 'Notificacion no encontrada'], 404);
        }

        return response()->json(['message' => 'Notificacion marcada como leida']);
    }
}
