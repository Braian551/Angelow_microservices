<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\LoginUserDTO;
use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Login Controller
 *
 * Handles user authentication via API.
 * Delegates business logic to AuthService.
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Authenticate a user.
     *
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $dto = LoginUserDTO::fromArray($request->validated());
            $result = $this->authService->login($dto);

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => [
                    'user' => [
                        'id' => $result['user']->id,
                        'name' => $result['user']->name,
                        'email' => $result['user']->email,
                        'phone' => $result['user']->phone,
                        'image' => $this->normalizeUserImagePath($result['user']->image),
                        'role' => $result['user']->role,
                        'created_at' => $result['user']->created_at?->toISOString(),
                    ],
                    'token' => $result['token'],
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (AuthException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Authenticate a user with Google (Firebase ID token).
     *
     * POST /api/auth/google
     */
    public function google(GoogleLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginWithGoogleToken($request->string('id_token')->toString());

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión con Google exitoso',
                'data' => [
                    'user' => [
                        'id' => $result['user']->id,
                        'name' => $result['user']->name,
                        'email' => $result['user']->email,
                        'phone' => $result['user']->phone,
                        'image' => $this->normalizeUserImagePath($result['user']->image),
                        'role' => $result['user']->role,
                        'created_at' => $result['user']->created_at?->toISOString(),
                    ],
                    'token' => $result['token'],
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (AuthException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Log out the authenticated user.
     *
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     *
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
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
     * Normaliza la ruta de imagen de usuario para el frontend SPA.
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
}
