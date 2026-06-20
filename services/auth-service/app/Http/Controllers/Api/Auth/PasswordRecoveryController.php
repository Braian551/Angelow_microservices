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
 * Controlador del flujo de recuperación de contraseña.
 *
 * Expone 4 endpoints públicos que orquestan el ciclo completo:
 * solicitar código, reenviar, verificar y restablecer contraseña.
 * Centraliza el manejo de errores AuthException via handleAction().
 */
class PasswordRecoveryController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $passwordRecoveryService,
    ) {}

    /**
     * Solicita un código de recuperación para el correo/teléfono dado.
     *
     * POST /api/auth/password-recovery/request-code
     * Recibe: identifier (email o teléfono)
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
     * Reenvía un nuevo código de recuperación (con cooldown anti-spam).
     *
     * POST /api/auth/password-recovery/resend-code
     * Recibe: identifier (email o teléfono)
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
     * Verifica el código ingresado y emite un session_token temporal.
     *
     * POST /api/auth/password-recovery/verify-code
     * Recibe: identifier, code (4 dígitos)
     * Retorna: session_token para el paso de restablecimiento
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
     * Restablece la contraseña usando el session_token emitido tras verificar el código.
     *
     * POST /api/auth/password-recovery/reset-password
     * Recibe: session_token, password, password_confirmation
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
     *
     * Evita repetir try/catch en cada método del controlador.
     * Usa el código HTTP de la excepción o 400 como fallback.
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
