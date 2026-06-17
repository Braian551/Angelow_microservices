<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\RegisterUserDTO;
use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de registro de usuarios.
 *
 * Maneja el registro vía API. Delega la lógica de negocio
 * a AuthService y usa RegisterRequest para validación.
 * Devuelve el usuario creado junto con un token Bearer de Sanctum.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Registra un nuevo usuario en el sistema.
     *
     * POST /api/auth/register
     * Espera: name, email, phone, password, password_confirmation, terms
     * Retorna: usuario creado + token de acceso (HTTP 201)
     * En caso de error (email duplicado, etc.) lanza AuthException.
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            // Convierte los datos validados en un DTO inmutable
            $dto = RegisterUserDTO::fromArray($request->validated());
            $result = $this->authService->register($dto);

            return response()->json([
                'success' => true,
                'message' => '¡Registro exitoso! Bienvenido/a a Angelow.',
                'data' => [
                    'user' => [
                        'id' => $result['user']->id,
                        'name' => $result['user']->name,
                        'email' => $result['user']->email,
                        'phone' => $result['user']->phone,
                        'image' => $result['user']->image,
                        'role' => $result['user']->role,
                        'created_at' => $result['user']->created_at?->toISOString(),
                    ],
                    'token' => $result['token'],
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (AuthException $e) {
            // Captura errores controlados como email duplicado
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}