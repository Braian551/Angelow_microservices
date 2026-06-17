<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Controlador de perfil del usuario autenticado.
 *
 * Permite actualizar nombre, teléfono, avatar y contraseña.
 * La subida de avatar reemplaza la imagen anterior usando
 * la lógica de reemplazo seguro (regla 31 del proyecto).
 */
class ProfileController extends Controller
{
    /**
     * Actualiza perfil básico y avatar del usuario autenticado.
     *
     * POST /api/auth/profile (requiere autenticación)
     * Campos: name (obligatorio), phone, image (archivo)
     * Si se sube imagen, elimina el avatar anterior y guarda el nuevo.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $user->name = trim((string) $data['name']);
        $user->phone = trim((string) ($data['phone'] ?? '')) ?: null;

        // Procesa la subida de imagen con reemplazo seguro del archivo anterior
        if ($request->hasFile('image')) {
            $uploaded = $request->file('image');
            $extension = strtolower((string) $uploaded->getClientOriginalExtension());
            $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
            $fileName = (string) $user->id . '_' . time() . '_' . Str::lower(Str::random(6)) . '.' . $safeExtension;

            $destination = public_path('uploads/users');
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $uploaded->move($destination, $fileName);
            $this->deleteOldAvatar((string) ($user->image ?? ''));
            $user->image = $fileName;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $this->normalizeUserImagePath($user->image),
                'role' => $user->role,
                'created_at' => $user->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Cambia la contraseña validando la contraseña actual primero.
     *
     * POST /api/auth/password (requiere autenticación)
     * Campos: current_password, password, password_confirmation
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:64', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check((string) $data['current_password'], (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta.',
            ], 422);
        }

        $user->password = (string) $data['password'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    /**
     * Normaliza la ruta de imagen para el frontend SPA.
     *
     * Si el path está vacío retorna default-avatar.png.
     * Si solo es un nombre de archivo (legacy), antepone uploads/users/.
     * Si ya tiene ruta, normaliza backslash a slash.
     */
    private function normalizeUserImagePath(?string $path): string
    {
        $cleanPath = trim((string) $path);
        if ($cleanPath === '') {
            return 'images/default-avatar.png';
        }

        if (str_contains($cleanPath, '/')) {
            return ltrim(str_replace('\\', '/', $cleanPath), '/');
        }

        return 'uploads/users/' . $cleanPath;
    }

    /**
     * Elimina avatar previo solo cuando pertenece a uploads/users.
     *
     * Valida que la ruta esté dentro de uploads/users para evitar
     * eliminar archivos fuera del directorio controlado (seguridad).
     * No hace nada si el archivo no existe físicamente.
     */
    private function deleteOldAvatar(string $storedPath): void
    {
        $cleanPath = trim($storedPath);
        if ($cleanPath === '') {
            return;
        }

        $normalized = str_replace('\\', '/', $cleanPath);

        // Previene path traversal
        if (str_contains($normalized, '..')) {
            return;
        }

        // Si es solo nombre de archivo, completa la ruta
        if (!str_contains($normalized, '/')) {
            $normalized = 'uploads/users/' . $normalized;
        }

        $normalized = ltrim($normalized, '/');

        // Solo elimina archivos dentro del directorio controlado
        if (!str_starts_with($normalized, 'uploads/users/')) {
            return;
        }

        $absolutePath = public_path($normalized);
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
