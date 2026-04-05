<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminNotificationController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    // ── Anuncios ────────────────────────────────────────────

    public function announcements(): JsonResponse
    {
        $items = $this->announcementsQuery()->orderByDesc('created_at')->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'             => ['required', 'string', 'max:30'],
            'title'            => ['required', 'string', 'max:150'],
            'message'          => ['nullable', 'string'],
            'subtitle'         => ['nullable', 'string', 'max:150'],
            'button_text'      => ['nullable', 'string', 'max:50'],
            'button_link'      => ['nullable', 'string', 'max:255'],
            'image'            => ['nullable', 'string', 'max:255'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'text_color'       => ['nullable', 'string', 'max:20'],
            'icon'             => ['nullable', 'string', 'max:50'],
            'priority'         => ['nullable', 'integer'],
            'is_active'        => ['nullable', 'boolean'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date'],
        ]);

        $payload = [
            'type'             => $data['type'],
            'title'            => $data['title'],
            'message'          => $data['message'] ?? null,
            'subtitle'         => $data['subtitle'] ?? null,
            'button_text'      => $data['button_text'] ?? null,
            'button_link'      => $data['button_link'] ?? null,
            'image'            => $data['image'] ?? null,
            'background_color' => $data['background_color'] ?? null,
            'text_color'       => $data['text_color'] ?? null,
            'icon'             => $data['icon'] ?? null,
            'priority'         => $data['priority'] ?? 0,
            'is_active'        => $data['is_active'] ?? true,
            'start_date'       => $data['start_date'] ?? null,
            'end_date'         => $data['end_date'] ?? null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $id = $this->announcementsQuery()->insertGetId($payload);

        return response()->json(['success' => true, 'message' => 'Anuncio creado.', 'id' => $id], 201);
    }

    public function updateAnnouncement(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'type'             => ['sometimes', 'string', 'max:30'],
            'title'            => ['sometimes', 'string', 'max:150'],
            'message'          => ['nullable', 'string'],
            'subtitle'         => ['nullable', 'string', 'max:150'],
            'button_text'      => ['nullable', 'string', 'max:50'],
            'button_link'      => ['nullable', 'string', 'max:255'],
            'image'            => ['nullable', 'string', 'max:255'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'text_color'       => ['nullable', 'string', 'max:20'],
            'icon'             => ['nullable', 'string', 'max:50'],
            'priority'         => ['nullable', 'integer'],
            'is_active'        => ['nullable', 'boolean'],
            'start_date'       => ['nullable', 'date'],
            'end_date'         => ['nullable', 'date'],
        ]);

        $payload = collect($data)->filter(fn($v) => $v !== null)->toArray();
        $payload['updated_at'] = now();

        $updated = $this->announcementsQuery()->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Anuncio no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Anuncio actualizado.']);
    }

    public function destroyAnnouncement(int $id): JsonResponse
    {
        $deleted = $this->announcementsQuery()->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Anuncio no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Anuncio eliminado.']);
    }

    /**
     * Usa legacy_mysql si la tabla announcements existe ahi; si no, usa conexion por defecto.
     */
    private function announcementsQuery()
    {
        try {
            $legacy = DB::connection(self::LEGACY_CONNECTION);
            if (Schema::connection(self::LEGACY_CONNECTION)->hasTable('announcements')) {
                return $legacy->table('announcements');
            }
        } catch (Throwable) {
            // fallback a conexion por defecto
        }

        return DB::table('announcements');
    }
}
