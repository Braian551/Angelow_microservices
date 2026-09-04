<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourierProfileRequest;
use App\Models\CourierDocument;
use App\Models\CourierLocation;
use App\Models\CourierProfile;
use App\Models\DeliveryAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CourierController extends Controller
{
    private const MOTORIZED_TYPES = ['motorcycle', 'car', 'truck'];

    public function profile(Request $request): JsonResponse
    {
        $profile = CourierProfile::query()
            ->with(['vehicle', 'documents'])
            ->where('user_id', $this->courierUser($request)['id'])
            ->first();

        return response()->json(['data' => $profile]);
    }

    public function saveProfile(CourierProfileRequest $request): JsonResponse
    {
        $user = $this->courierUser($request);
        $data = $request->validated();

        $vehicleType = $data['vehicle']['type'];

        $existingProfile = CourierProfile::query()
            ->where('user_id', $user['id'])
            ->with('documents')
            ->first();
        $existingDocumentTypes = $existingProfile?->documents->pluck('type')->all() ?? [];
        $incomingDocumentTypes = array_keys($request->file('documents', []));
        $missingDocuments = $this->missingRequiredDocuments(
            array_values(array_unique([...$existingDocumentTypes, ...$incomingDocumentTypes])),
            $vehicleType,
            (bool) ($data['technical_inspection_not_applicable'] ?? false),
        );
        if ($existingProfile?->status === 'rejected') {
            $documentsToReplace = $existingProfile->documents
                ->where('status', 'rejected')
                ->whereIn(
                    'type',
                    $this->requiredDocumentTypes(
                        $vehicleType,
                        (bool) ($data['technical_inspection_not_applicable'] ?? false),
                    ),
                )
                ->pluck('type')
                ->all();
            $missingDocuments = array_values(array_unique([
                ...$missingDocuments,
                ...array_diff($documentsToReplace, $incomingDocumentTypes),
            ]));
        }
        if ($missingDocuments !== []) {
            return response()->json([
                'message' => 'Adjunta todos los documentos requeridos.',
                'missing_documents' => $missingDocuments,
            ], 422);
        }

        $documentHash = hash('sha256', mb_strtolower(trim($data['document_type'] . ':' . $data['document_number'])));
        $duplicate = CourierProfile::query()
            ->where('document_number_hash', $documentHash)
            ->where('user_id', '!=', $user['id'])
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'Este documento ya está vinculado a otra solicitud.'], 409);
        }

        $profile = DB::transaction(function () use ($request, $data, $user, $documentHash, $vehicleType): CourierProfile {
            $profile = CourierProfile::query()->updateOrCreate(
                ['user_id' => $user['id']],
                [
                    'email' => $user['email'],
                    'document_type' => $data['document_type'],
                    'document_number' => trim($data['document_number']),
                    'document_number_hash' => $documentHash,
                    'birth_date' => $data['birth_date'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'status' => 'pending',
                    'rejection_reason' => null,
                    'is_active' => true,
                    'terms_version' => $data['terms_version'],
                    'terms_accepted_at' => now(),
                ],
            );

            $profile->vehicle()->updateOrCreate(
                ['courier_profile_id' => $profile->id],
                ['type' => $vehicleType] + (!in_array($vehicleType, self::MOTORIZED_TYPES, true) ? [
                    'make_id' => null, 'make_name' => null, 'model_id' => null,
                    'model_name' => null, 'color_name' => null, 'color_hex' => null,
                    'year' => null, 'plate' => null, 'ownership_type' => null,
                ] : $data['vehicle']),
            );

            foreach ($request->file('documents', []) as $type => $file) {
                $this->storeDocument($profile, (string) $type, $file, $data['document_expiries'][$type] ?? null);
            }

            return $profile->fresh(['vehicle', 'documents']);
        });

        $this->notifyUser(
            $profile,
            'Solicitud de repartidor recibida',
            'Recibimos tus datos. Te avisaremos cuando finalice la revisión.',
            false,
        );
        $this->notifyAdminsOnApplication($profile);

        return response()->json([
            'message' => 'Solicitud enviada para revisión.',
            'data' => $profile,
        ], 201);
    }

    public function assignments(Request $request): JsonResponse
    {
        $profile = $this->approvedProfile($request);
        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        $scope = $request->string('scope', 'available')->toString();
        $query = DeliveryAssignment::query()->latest();
        if ($scope === 'mine') {
            $query->where('courier_profile_id', $profile->id)
                ->whereNotIn('status', ['delivered', 'cancelled']);
        } else {
            $query->whereNull('courier_profile_id')->where('status', 'pending');
        }

        return response()->json(['data' => $query->limit(100)->get()]);
    }

    public function mapConfig(Request $request): JsonResponse
    {
        $profile = $this->approvedProfile($request);
        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        $accessToken = trim((string) config('services.mapbox.access_token'));
        if ($accessToken === '') {
            return response()->json([
                'message' => 'La navegación no está disponible temporalmente.',
            ], 503);
        }

        return response()->json([
            'data' => ['access_token' => $accessToken],
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function accept(Request $request, int $assignmentId): JsonResponse
    {
        $profile = $this->approvedProfile($request);
        if ($profile instanceof JsonResponse) {
            return $profile;
        }

        $deliveryCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $assignment = DB::transaction(function () use ($assignmentId, $profile, $deliveryCode): ?DeliveryAssignment {
            $row = DeliveryAssignment::query()->lockForUpdate()->find($assignmentId);
            if (!$row || $row->status !== 'pending' || $row->courier_profile_id !== null) {
                return null;
            }
            $row->update([
                'courier_profile_id' => $profile->id,
                'status' => 'assigned',
                'delivery_code_hash' => Hash::make($deliveryCode),
                'delivery_code' => $deliveryCode,
                'accepted_at' => now(),
            ]);
            return $row->fresh();
        });

        if (!$assignment) {
            return response()->json(['message' => 'La entrega ya fue tomada por otro repartidor.'], 409);
        }

        if (!$this->updateOrderStatus($assignment, 'shipped', 'Orden aceptada por un repartidor.')) {
            $assignment->update([
                'courier_profile_id' => null,
                'status' => 'pending',
                'delivery_code_hash' => null,
                'delivery_code' => null,
                'accepted_at' => null,
            ]);
            return response()->json(['message' => 'No fue posible asignar la orden. Inténtalo nuevamente.'], 503);
        }

        $customerNotified = $this->notifyCustomerWithCode($assignment, $deliveryCode);
        $this->notifyAdminsOnAcceptance($assignment, $profile);

        return response()->json([
            'message' => $customerNotified
                ? 'Entrega asignada. El cliente recibió el código en sus notificaciones y correo.'
                : 'Entrega asignada. El aviso al cliente quedó pendiente de reintento.',
            'customer_notified' => $customerNotified,
            'data' => $assignment->fresh(),
        ]);
    }

    public function startRoute(Request $request, int $assignmentId): JsonResponse
    {
        $data = $request->validate(['share_location' => ['required', 'boolean']]);
        $assignment = $this->ownedAssignment($request, $assignmentId);
        if ($assignment instanceof JsonResponse) {
            return $assignment;
        }
        if (!in_array($assignment->status, ['assigned', 'en_route'], true)) {
            return response()->json(['message' => 'Esta entrega no puede iniciar ruta.'], 422);
        }

        $assignment->update([
            'status' => 'en_route',
            'sharing_location' => (bool) $data['share_location'],
            'route_started_at' => $assignment->route_started_at ?? now(),
        ]);

        return response()->json(['message' => 'Ruta iniciada.', 'data' => $assignment->fresh()]);
    }

    public function location(Request $request, int $assignmentId): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);
        $assignment = $this->ownedAssignment($request, $assignmentId);
        if ($assignment instanceof JsonResponse) {
            return $assignment;
        }
        if ($assignment->status !== 'en_route') {
            return response()->json(['message' => 'La ubicación solo se registra durante una ruta activa.'], 422);
        }

        $location = CourierLocation::query()->create([
            'delivery_assignment_id' => $assignment->id,
            ...$data,
            'recorded_at' => now(),
        ]);

        return response()->json(['data' => $location], 201);
    }

    public function arrive(Request $request, int $assignmentId): JsonResponse
    {
        $assignment = $this->ownedAssignment($request, $assignmentId);
        if ($assignment instanceof JsonResponse) {
            return $assignment;
        }
        if ($assignment->status !== 'en_route') {
            return response()->json(['message' => 'Primero debes iniciar la ruta.'], 422);
        }
        $assignment->update(['status' => 'arrived', 'arrived_at' => now()]);
        return response()->json(['message' => 'Llegada registrada.', 'data' => $assignment->fresh()]);
    }

    public function complete(Request $request, int $assignmentId): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'regex:/^[0-9]{6}$/']]);
        $assignment = $this->ownedAssignment($request, $assignmentId);
        if ($assignment instanceof JsonResponse) {
            return $assignment;
        }
        if ($assignment->status !== 'arrived') {
            return response()->json(['message' => 'Registra la llegada antes de finalizar.'], 422);
        }
        if (!Hash::check($data['code'], (string) $assignment->delivery_code_hash)) {
            return response()->json(['message' => 'El código de entrega no es válido.'], 422);
        }
        if (!$this->updateOrderStatus($assignment, 'delivered', 'Entrega confirmada con código del cliente.')) {
            return response()->json(['message' => 'No fue posible finalizar la orden.'], 503);
        }

        $assignment->update([
            'status' => 'delivered',
            'sharing_location' => false,
            'delivered_at' => now(),
            'delivery_code_hash' => null,
            'delivery_code' => null,
        ]);
        $this->notifyUserByAssignment($assignment, 'Pedido entregado', 'La entrega fue confirmada correctamente.', true);

        return response()->json(['message' => 'Entrega finalizada.', 'data' => $assignment->fresh()]);
    }

    private function approvedProfile(Request $request): CourierProfile|JsonResponse
    {
        $profile = CourierProfile::query()->where('user_id', $this->courierUser($request)['id'])->first();
        if (!$profile) {
            return response()->json(['message' => 'Completa tu perfil de repartidor.'], 409);
        }
        if (!$profile->is_active || $profile->status !== 'approved') {
            return response()->json([
                'message' => $profile->status === 'pending'
                    ? 'Tu solicitud sigue en revisión.'
                    : 'Tu perfil no está habilitado para recibir entregas.',
                'status' => $profile->status,
            ], 403);
        }
        return $profile;
    }

    private function ownedAssignment(Request $request, int $assignmentId): DeliveryAssignment|JsonResponse
    {
        $profile = $this->approvedProfile($request);
        if ($profile instanceof JsonResponse) {
            return $profile;
        }
        $assignment = DeliveryAssignment::query()
            ->where('id', $assignmentId)
            ->where('courier_profile_id', $profile->id)
            ->first();
        return $assignment ?: response()->json(['message' => 'Entrega no encontrada.'], 404);
    }

    /** @return array<string, mixed> */
    private function courierUser(Request $request): array
    {
        return (array) $request->input('_courier_user', []);
    }

    private function storeDocument(CourierProfile $profile, string $type, mixed $file, ?string $expiry): void
    {
        $allowed = ['identity_front', 'identity_back', 'profile_photo',
            'driving_license', 'vehicle_registration', 'soat', 'technical_inspection'];
        if (!in_array($type, $allowed, true)) {
            return;
        }

        $existing = CourierDocument::query()
            ->where('courier_profile_id', $profile->id)
            ->where('type', $type)
            ->first();
        $directory = public_path("uploads/couriers/{$profile->id}");
        File::ensureDirectoryExists($directory);
        $filename = $type . '-' . Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($directory, $filename);
        $path = "uploads/couriers/{$profile->id}/{$filename}";

        if ($existing && str_starts_with($existing->path, "uploads/couriers/{$profile->id}/")) {
            File::delete(public_path($existing->path));
        }

        CourierDocument::query()->updateOrCreate(
            ['courier_profile_id' => $profile->id, 'type' => $type],
            ['path' => $path, 'expires_at' => $expiry, 'status' => 'pending', 'review_note' => null],
        );
    }

    /** @param array<int, string> $present */
    private function missingRequiredDocuments(array $present, string $vehicleType, bool $inspectionNotApplicable): array
    {
        return array_values(array_diff(
            $this->requiredDocumentTypes($vehicleType, $inspectionNotApplicable),
            $present,
        ));
    }

    /** @return array<int, string> */
    private function requiredDocumentTypes(string $vehicleType, bool $inspectionNotApplicable): array
    {
        $required = ['identity_front', 'identity_back', 'profile_photo'];
        if (in_array($vehicleType, self::MOTORIZED_TYPES, true)) {
            array_push($required, 'driving_license', 'vehicle_registration', 'soat');
            if (!$inspectionNotApplicable) {
                $required[] = 'technical_inspection';
            }
        }

        return $required;
    }

    private function updateOrderStatus(DeliveryAssignment $assignment, string $status, string $description): bool
    {
        try {
            $url = rtrim((string) config('services.orders.base_url'), '/') . "/orders/{$assignment->order_id}/status";
            return Http::timeout(8)->patch($url, [
                'status' => $status,
                'source' => $assignment->order_source,
                'changed_by_name' => 'Angelow Repartidor',
                'description' => $description,
            ])->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function notifyCustomerWithCode(DeliveryAssignment $assignment, string $code): bool
    {
        return $this->notifyUserByAssignment(
            $assignment,
            'Tu pedido ya tiene repartidor',
            "Código de entrega: {$code}. Compártelo únicamente cuando recibas tu pedido.",
            true,
        );
    }

    private function notifyAdminsOnAcceptance(DeliveryAssignment $assignment, CourierProfile $profile): void
    {
        try {
            $authUrl = rtrim((string) config('services.auth.base_url'), '/');
            $response = Http::withHeaders([
                'X-Internal-Token' => (string) config('services.auth.internal_token'),
            ])->timeout(5)->get($authUrl . '/internal/users/profiles', ['role' => 'admin']);

            foreach ((array) $response->json('data', []) as $admin) {
                $this->sendNotification([
                    'user_id' => $admin['id'] ?? null,
                    'user_email' => $admin['email'] ?? null,
                    'title' => 'Entrega aceptada',
                    'message' => "El repartidor {$profile->email} aceptó la orden {$assignment->order_number}.",
                    'event_key' => 'delivery_assigned',
                    'related_entity_type' => 'order',
                    'related_entity_id' => $assignment->order_id,
                    'send_push' => true,
                    'send_email' => false,
                ]);
            }
        } catch (Throwable) {
            // La asignación no se revierte si un canal administrativo está temporalmente indisponible.
        }
    }

    private function notifyAdminsOnApplication(CourierProfile $profile): void
    {
        try {
            $authUrl = rtrim((string) config('services.auth.base_url'), '/');
            $response = Http::withHeaders([
                'X-Internal-Token' => (string) config('services.auth.internal_token'),
            ])->timeout(5)->get($authUrl . '/internal/users/profiles', ['role' => 'admin']);

            foreach ((array) $response->json('data', []) as $admin) {
                $this->sendNotification([
                    'user_id' => $admin['id'] ?? null,
                    'user_email' => $admin['email'] ?? null,
                    'title' => 'Nueva solicitud de repartidor',
                    'message' => "{$profile->email} envió una solicitud y sus documentos están pendientes de revisión.",
                    'event_key' => 'courier_application',
                    'related_entity_type' => 'courier',
                    'related_entity_id' => $profile->id,
                    'send_push' => true,
                    'send_email' => false,
                ]);
            }
        } catch (Throwable) {
            // La solicitud permanece disponible en el panel aunque falle el canal de notificaciones.
        }
    }

    private function notifyUserByAssignment(DeliveryAssignment $assignment, string $title, string $message, bool $email): bool
    {
        return $this->sendNotification([
            'user_id' => $assignment->customer_user_id,
            'user_email' => $assignment->customer_email,
            'title' => $title,
            'message' => $message,
            'event_key' => 'delivery_update',
            'related_entity_type' => 'order',
            'related_entity_id' => $assignment->order_id,
            'send_push' => true,
            'send_email' => $email,
        ]);
    }

    private function notifyUser(CourierProfile $profile, string $title, string $message, bool $email): bool
    {
        return $this->sendNotification([
            'user_id' => $profile->user_id,
            'user_email' => $profile->email,
            'title' => $title,
            'message' => $message,
            'event_key' => 'courier_application',
            'related_entity_type' => 'courier',
            'related_entity_id' => $profile->id,
            'send_push' => true,
            'send_email' => $email,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function sendNotification(array $payload): bool
    {
        try {
            $response = Http::retry(2, 150)
                ->timeout(8)
                ->post(rtrim((string) config('services.notifications.base_url'), '/') . '/notifications', $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('No se pudo entregar una notificación desde shipping-service.', [
                'event_key' => $payload['event_key'] ?? null,
                'related_entity_id' => $payload['related_entity_id'] ?? null,
                'status' => $response->status(),
            ]);
        } catch (Throwable $exception) {
            // La operación principal no debe revertirse por indisponibilidad temporal de notificaciones.
            Log::warning('El servicio de notificaciones no está disponible.', [
                'event_key' => $payload['event_key'] ?? null,
                'related_entity_id' => $payload['related_entity_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }
}
