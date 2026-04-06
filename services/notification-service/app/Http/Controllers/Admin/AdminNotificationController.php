<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AdminNotificationController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    // ── Anuncios ────────────────────────────────────────────

    public function announcements(): JsonResponse
    {
        $items = $this->announcementsQuery()
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($item) => $this->transformAnnouncement($item));

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function storeAnnouncement(Request $request): JsonResponse
    {
        if ($this->announcementsQuery()->count() >= 2) {
            return response()->json(['success' => false, 'message' => 'Ya existen 2 anuncios. Debes eliminar uno antes de agregar otro.'], 422);
        }

        $payload = $this->buildAnnouncementPayload($request, false);

        $id = $this->announcementsQuery()->insertGetId($payload);

        return response()->json(['success' => true, 'message' => 'Anuncio creado.', 'id' => $id], 201);
    }

    public function updateAnnouncement(Request $request, int $id): JsonResponse
    {
        $current = $this->announcementsQuery()->where('id', $id)->first();

        if (!$current) {
            return response()->json(['success' => false, 'message' => 'Anuncio no encontrado.'], 404);
        }

        $payload = $this->buildAnnouncementPayload($request, true, $current);

        $updated = $this->announcementsQuery()->where('id', $id)->update($payload);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Anuncio no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Anuncio actualizado.']);
    }

    public function destroyAnnouncement(int $id): JsonResponse
    {
        $current = $this->announcementsQuery()->where('id', $id)->first();
        $deleted = $this->announcementsQuery()->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Anuncio no encontrado.'], 404);
        }

        $this->deleteStoredImage($current?->image ?? null);

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

    private function buildAnnouncementPayload(Request $request, bool $partial, object|null $current = null): array
    {
        $data = $request->validate([
            'type' => [$partial ? 'sometimes' : 'required', 'string', 'max:30'],
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:150'],
            'message' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'subtitle' => ['nullable', 'string', 'max:150'],
            'button_text' => ['nullable', 'string', 'max:50'],
            'button_link' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'image_file' => ['nullable', 'file', 'image', 'max:3072'],
        ]);

        $payload = [];
        $message = $data['message'] ?? $data['content'] ?? null;
        $buttonLink = $data['button_link'] ?? $data['url'] ?? null;
        $type = array_key_exists('type', $data) ? $this->normalizeAnnouncementType($data['type']) : null;
        $priority = array_key_exists('priority', $data) ? $this->normalizeAnnouncementPriority($data['priority']) : null;
        $uploadedImage = $this->extractUploadedImage($request, $data);

        if ($type !== null) {
            $payload['type'] = $type;
        }

        if (array_key_exists('title', $data)) {
            $payload['title'] = trim((string) $data['title']);
        }

        if (array_key_exists('message', $data) || array_key_exists('content', $data)) {
            $payload['message'] = $this->nullableTrim($message);
        }

        if (array_key_exists('subtitle', $data)) {
            $payload['subtitle'] = $this->nullableTrim($data['subtitle'] ?? null);
        }

        if (array_key_exists('button_text', $data)) {
            $payload['button_text'] = $this->nullableTrim($data['button_text'] ?? null);
        }

        if (array_key_exists('button_link', $data) || array_key_exists('url', $data)) {
            $payload['button_link'] = $this->nullableTrim($buttonLink);
        }

        if ($uploadedImage !== null) {
            $payload['image'] = $uploadedImage;
            $this->deleteStoredImage($current?->image ?? null);
        } elseif (array_key_exists('image', $data) && is_string($data['image'])) {
            $payload['image'] = $this->nullableTrim($data['image']);
        }

        if (array_key_exists('background_color', $data)) {
            $payload['background_color'] = $this->nullableTrim($data['background_color'] ?? null);
        }

        if (array_key_exists('text_color', $data)) {
            $payload['text_color'] = $this->nullableTrim($data['text_color'] ?? null);
        }

        if (array_key_exists('icon', $data)) {
            $payload['icon'] = $this->nullableTrim($data['icon'] ?? null);
        }

        if ($priority !== null) {
            $payload['priority'] = $priority;
        }

        if (array_key_exists('is_active', $data) || array_key_exists('active', $data)) {
            $payload['is_active'] = (bool) ($data['is_active'] ?? $data['active'] ?? false);
        }

        if (array_key_exists('start_date', $data)) {
            $payload['start_date'] = $data['start_date'] ?? null;
        }

        if (array_key_exists('end_date', $data)) {
            $payload['end_date'] = $data['end_date'] ?? null;
        }

        $payload['updated_at'] = now();

        if (!$partial) {
            $payload['created_at'] = now();
        }

        return $payload;
    }

    private function extractUploadedImage(Request $request, array $data): ?string
    {
        $file = $request->file('image_file');

        if (!$file && ($data['image'] ?? null) instanceof UploadedFile) {
            $file = $data['image'];
        }

        if (!$file instanceof UploadedFile) {
            return null;
        }

        $directory = public_path('uploads/announcements');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = 'announcement_' . now()->timestamp . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/announcements/' . $filename;
    }

    private function deleteStoredImage(?string $path): void
    {
        $cleanPath = trim((string) $path);
        if ($cleanPath === '' || !str_starts_with($cleanPath, 'uploads/announcements/')) {
            return;
        }

        $absolutePath = public_path($cleanPath);
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function normalizeAnnouncementType(string $type): string
    {
        $normalized = Str::lower(trim($type));

        return match ($normalized) {
            'bar', 'top_bar' => 'top_bar',
            'banner', 'promo_banner' => 'promo_banner',
            default => $normalized,
        };
    }

    private function normalizeAnnouncementPriority(mixed $priority): int
    {
        if (is_numeric($priority)) {
            return (int) $priority;
        }

        return match (Str::lower(trim((string) $priority))) {
            'high', 'alta' => 10,
            'medium', 'media' => 5,
            'low', 'baja' => 1,
            default => 0,
        };
    }

    private function transformAnnouncement(object $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'title' => $item->title,
            'message' => $item->message,
            'content' => $item->message,
            'subtitle' => $item->subtitle,
            'button_text' => $item->button_text,
            'button_link' => $item->button_link,
            'url' => $item->button_link,
            'image' => $item->image,
            'background_color' => $item->background_color,
            'text_color' => $item->text_color,
            'icon' => $item->icon,
            'priority' => (int) ($item->priority ?? 0),
            'is_active' => (bool) ($item->is_active ?? false),
            'active' => (bool) ($item->is_active ?? false),
            'start_date' => $item->start_date,
            'end_date' => $item->end_date,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    private function nullableTrim(mixed $value): ?string
    {
        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }
}
