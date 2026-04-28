<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordRecoveryCodeRequest;
use App\Http\Requests\PasswordRecoveryResetRequest;
use App\Http\Requests\PasswordRecoveryVerifyCodeRequest;
use App\Services\PasswordRecoveryService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de recuperación de contraseña.
 */
class PasswordRecoveryController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $passwordRecoveryService,
    ) {}

    /**
     * POST /api/auth/password-recovery/request-code
     */
    public function requestCode(PasswordRecoveryCodeRequest $request): JsonResponse
    {
        return $this->handleAction(function () use ($request) {
            $result = $this->passwordRecoveryService->requestCode(
                $request->string('identifier')->toString(),
                false
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        });
    }

    /**
     * POST /api/auth/password-recovery/resend-code
     */
    public function resendCode(PasswordRecoveryCodeRequest $request): JsonResponse
    {
        return $this->handleAction(function () use ($request) {
            $result = $this->passwordRecoveryService->requestCode(
                $request->string('identifier')->toString(),
                true
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        });
    }

    /**
     * POST /api/auth/password-recovery/verify-code
     */
    public function verifyCode(PasswordRecoveryVerifyCodeRequest $request): JsonResponse
    {
        return $this->handleAction(function () use ($request) {
            $result = $this->passwordRecoveryService->verifyCode(
                $request->string('identifier')->toString(),
                $request->string('code')->toString(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Código verificado. Ahora crea tu nueva contraseña.',
                'data' => $result,
            ]);
        });
    }

    /**
     * POST /api/auth/password-recovery/reset-password
     */
    public function resetPassword(PasswordRecoveryResetRequest $request): JsonResponse
    {
        return $this->handleAction(function () use ($request) {
            $this->passwordRecoveryService->resetPassword(
                $request->string('session_token')->toString(),
                $request->string('password')->toString(),
                $request->string('password_confirmation')->toString(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Tu contraseña fue actualizada correctamente. Ya puedes iniciar sesión.',
                'data' => [],
            ]);
        });
    }

    /**
     * Centraliza manejo de AuthException para respuestas consistentes.
     */
    private function handleAction(\Closure $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (AuthException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getCode() > 0 ? $exception->getCode() : 400);
        }
    }
}
