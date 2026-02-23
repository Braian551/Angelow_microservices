<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\LoginUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Exceptions\AuthException;
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
                        'id'    => $result['user']->id,
                        'name'  => $result['user']->name,
                        'email' => $result['user']->email,
                        'phone' => $result['user']->phone,
                        'role'  => $result['user']->role,
                    ],
                    'token'      => $result['token'],
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
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role,
            ],
        ]);
    }
}
