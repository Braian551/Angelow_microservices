<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryTrackingController extends Controller
{
    public function show(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40', 'required_without:user_email'],
            'user_email' => ['nullable', 'email', 'max:100', 'required_without:user_id'],
        ]);
        $assignment = DeliveryAssignment::query()->where('order_id', $orderId)->first();
        if (!$assignment) {
            return response()->json(['data' => null]);
        }

        $ownsOrder = ($data['user_id'] ?? null) && $assignment->customer_user_id === $data['user_id'];
        $ownsOrder = $ownsOrder || (($data['user_email'] ?? null)
            && mb_strtolower((string) $assignment->customer_email) === mb_strtolower($data['user_email']));
        if (!$ownsOrder) {
            return response()->json(['message' => 'No puedes consultar esta entrega.'], 403);
        }

        $location = null;
        if ($assignment->sharing_location && in_array($assignment->status, ['en_route', 'arrived'], true)) {
            $location = $assignment->locations()->latest('recorded_at')->first();
        }

        return response()->json([
            'data' => [
                'status' => $assignment->status,
                'sharing_location' => (bool) $location,
                'location' => $location,
                'delivery_code' => in_array($assignment->status, ['assigned', 'en_route', 'arrived'], true)
                    ? $assignment->delivery_code
                    : null,
                'destination' => [
                    'latitude' => $assignment->destination_latitude,
                    'longitude' => $assignment->destination_longitude,
                ],
            ],
        ]);
    }
}
