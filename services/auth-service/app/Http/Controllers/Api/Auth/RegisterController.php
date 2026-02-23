<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\RegisterUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use App\Exceptions\AuthException;
use Illuminate\Http\JsonResponse;

/**
 * Register Controller
 *
 * Handles user registration via API.
 * Delegates business logic to AuthService.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Register a new user.
     *
     * POST /api/auth/register
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            $dto = RegisterUserDTO::fromArray($request->validated());
            $result = $this->authService->register($dto);

            return response()->json([
                'success' => true,
                'message' => '¡Registro exitoso! Bienvenido/a a Angelow.',
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
            ], 201);
        } catch (AuthException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
