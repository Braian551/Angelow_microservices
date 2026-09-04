<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierDocument;
use App\Models\CourierProfile;
use App\Models\DeliveryAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Throwable;

class AdminCourierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'inactive'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = CourierProfile::query()->with(['vehicle', 'documents'])->latest();
        if (filled($data['search'] ?? null)) {
            $search = trim($data['search']);
            $query->where(fn ($builder) => $builder
                ->where('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }
        if (filled($data['status'] ?? null)) {
            $query->where('status', $data['status']);
        }

        return response()->json(['data' => $query->paginate((int) ($data['per_page'] ?? 20))]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => CourierProfile::query()->with(['vehicle', 'documents'])->findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $profile = CourierProfile::query()->findOrFail($id);
        $data = $request->validate([
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'regex:/^3[0-9]{9}$/'],
            'address' => ['sometimes', 'string', 'max:180'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'documents' => ['nullable', 'array'],
            'documents.*.status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'documents.*.review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $reviewStatus = $data['status'] ?? null;
        $documents = collect($data['documents'] ?? []);

        if ($reviewStatus === 'rejected') {
            if (blank($data['rejection_reason'] ?? null)) {
                return response()->json(['message' => 'Explica al repartidor qué debe corregir.'], 422);
            }

            $rejectedDocuments = $documents->filter(
                static fn (array $document): bool => ($document['status'] ?? null) === 'rejected',
            );
            if ($rejectedDocuments->isEmpty()) {
                return response()->json(['message' => 'Selecciona al menos un documento que requiera cambios.'], 422);
            }
            if ($rejectedDocuments->contains(
                static fn (array $document): bool => blank($document['review_note'] ?? null),
            )) {
                return response()->json(['message' => 'Indica el cambio requerido en cada documento seleccionado.'], 422);
            }
        }

        if ($reviewStatus === 'approved') {
            $data['rejection_reason'] = null;
            $documents = $profile->documents()->get()->mapWithKeys(
                static fn (CourierDocument $document): array => [
                    $document->id => ['status' => 'approved', 'review_note' => null],
                ],
            );
        }

        $profile->fill(collect($data)->except(['documents'])->all());
        if (isset($data['status'])) {
            $profile->reviewed_at = now();
            $profile->reviewed_by = (string) data_get($request->input('_admin_user'), 'id');
            $profile->is_active = $data['status'] !== 'rejected';
        }
        $profile->save();

        foreach ($documents as $documentId => $documentData) {
            CourierDocument::query()
                ->where('courier_profile_id', $profile->id)
                ->whereKey($documentId)
                ->update($documentData);
        }

        if (isset($data['status'])) {
            $this->notifyReview($profile);
        }

        return response()->json([
            'message' => 'Repartidor actualizado.',
            'data' => $profile->fresh(['vehicle', 'documents']),
        ]);
    }

    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $profile = CourierProfile::query()->findOrFail($id);
        $profile->update([
            'is_active' => (bool) $data['is_active'],
            'status' => $data['is_active'] ? ($profile->status === 'inactive' ? 'approved' : $profile->status) : 'inactive',
            'reviewed_at' => now(),
            'reviewed_by' => (string) data_get($request->input('_admin_user'), 'id'),
        ]);
        $this->notifyReview($profile);

        return response()->json(['message' => $profile->is_active ? 'Repartidor activado.' : 'Repartidor desactivado.']);
    }

    public function deliveries(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:20'],
            'courier_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = DeliveryAssignment::query()->with('courier.vehicle')->latest();
        if (filled($data['status'] ?? null)) {
            $query->where('status', $data['status']);
        }
        if (filled($data['courier_id'] ?? null)) {
            $query->where('courier_profile_id', $data['courier_id']);
        }

        return response()->json(['data' => $query->paginate((int) ($data['per_page'] ?? 20))]);
    }

    public function summary(): JsonResponse
    {
        $pendingApplicationIds = CourierProfile::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values();
        $pendingDeliveryIds = DeliveryAssignment::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values();

        return response()->json(['data' => [
            'pending_applications' => $pendingApplicationIds->count(),
            'pending_application_ids' => $pendingApplicationIds,
            'active_couriers' => CourierProfile::query()->where('status', 'approved')->where('is_active', true)->count(),
            'pending_deliveries' => $pendingDeliveryIds->count(),
            'pending_delivery_ids' => $pendingDeliveryIds,
            'active_deliveries' => DeliveryAssignment::query()->whereIn('status', ['assigned', 'en_route', 'arrived'])->count(),
        ]]);
    }

    private function notifyReview(CourierProfile $profile): void
    {
        $messages = [
            'approved' => ['Solicitud aprobada', 'Tu perfil fue aprobado. Ya puedes aceptar entregas.'],
            'rejected' => ['Solicitud requiere cambios', $profile->rejection_reason ?: 'Revisa los datos de tu solicitud.'],
            'inactive' => ['Perfil desactivado', 'Tu perfil de repartidor fue desactivado.'],
        ];
        [$title, $message] = $messages[$profile->status] ?? ['Solicitud actualizada', 'El estado de tu solicitud cambió.'];
        try {
            Http::timeout(5)->post(rtrim((string) config('services.notifications.base_url'), '/') . '/notifications', [
                'user_id' => $profile->user_id,
                'user_email' => $profile->email,
                'title' => $title,
                'message' => $message,
                'event_key' => 'courier_application',
                'related_entity_type' => 'courier',
                'related_entity_id' => $profile->id,
                'send_push' => true,
                'send_email' => true,
            ]);
        } catch (Throwable) {
            // El cambio administrativo permanece aunque el canal esté temporalmente fuera de línea.
        }
    }
}
