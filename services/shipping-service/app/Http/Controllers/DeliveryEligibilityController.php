<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAssignment;
use App\Models\LegacyUserAddress;
use App\Models\ShippingMethod;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DeliveryEligibilityController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (!$this->hasInternalAccess($request)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'order_number' => ['nullable', 'string', 'max:30'],
            'order_source' => ['nullable', 'string', 'max:20'],
            'customer_user_id' => ['nullable', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'shipping_method_id' => ['nullable', 'integer'],
            'shipping_method_name' => ['nullable', 'string', 'max:100'],
            'delivery_time' => ['nullable', 'string', 'max:80'],
            'shipping_address_id' => ['nullable', 'integer', 'min:1'],
            'destination_address' => ['nullable', 'string'],
            'destination_city' => ['nullable', 'string', 'max:100'],
            'destination_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $shippingMethodId = (int) ($data['shipping_method_id'] ?? 0);
        $method = $shippingMethodId > 0 ? $this->findShippingMethod($shippingMethodId) : null;
        $methodName = trim((string) ($data['shipping_method_name'] ?? $method?->name ?? 'Envío estándar'));
        $deliveryTime = trim((string) ($data['delivery_time'] ?? $method?->delivery_time ?? '')) ?: null;
        $normalized = Str::of($methodName)->lower()->ascii()->value();
        if (str_contains($normalized, 'punto de entrega') || str_contains($normalized, 'recogida') || str_contains($normalized, 'pickup')) {
            return response()->json(['message' => 'El método no requiere repartidor.', 'eligible' => false]);
        }

        $address = isset($data['shipping_address_id'])
            ? $this->findAddress($data['shipping_address_id'])
            : null;
        $data['destination_latitude'] ??= $address?->gps_latitude;
        $data['destination_longitude'] ??= $address?->gps_longitude;
        $data['destination_address'] ??= $address?->address;
        $data['destination_city'] ??= $address?->city;
        $data['shipping_method_id'] = $shippingMethodId > 0 ? $shippingMethodId : null;
        $data['shipping_method_name'] = $methodName;
        $data['delivery_time'] = $deliveryTime;
        unset($data['shipping_address_id']);

        $assignment = DeliveryAssignment::query()->firstOrNew(['order_id' => $data['order_id']]);
        if ($assignment->exists && $assignment->courier_profile_id !== null) {
            return response()->json(['message' => 'La orden ya está asignada.', 'data' => $assignment]);
        }
        $assignment->fill($data + ['status' => 'pending'])->save();

        return response()->json(['message' => 'Entrega publicada.', 'eligible' => true, 'data' => $assignment], 201);
    }

    private function findShippingMethod(int $id): ?object
    {
        $method = ShippingMethod::query()->find($id);
        if ($method !== null) {
            return $method;
        }

        try {
            return DB::connection('legacy_mysql')
                ->table('shipping_methods')
                ->where('id', $id)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function findAddress(int $id): UserAddress|LegacyUserAddress|null
    {
        $address = UserAddress::query()->find($id);
        if ($address !== null) {
            return $address;
        }

        try {
            return LegacyUserAddress::query()->find($id);
        } catch (Throwable) {
            return null;
        }
    }

    private function hasInternalAccess(Request $request): bool
    {
        $expected = trim((string) config('services.internal_token'));
        $received = trim((string) $request->header('X-Internal-Token'));
        return $expected !== '' && $received !== '' && hash_equals($expected, $received);
    }
}
