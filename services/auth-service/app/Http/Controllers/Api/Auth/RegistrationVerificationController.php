<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationCodeRequest;
use App\Http\Requests\RegistrationVerifyCodeRequest;
use App\Services\RegistrationVerificationService;
use App\Services\TurnstileVerificationService;
use Illuminate\Http\JsonResponse;

/**
 * Controla el envío y validación del código de correo previo al registro.
 */
class RegistrationVerificationController extends Controller
{
    public function __construct(
        private readonly RegistrationVerificationService $registrationVerificationService,
        private readonly TurnstileVerificationService $turnstileVerificationService,
    ) {}

    /**
     * Envía un código al correo capturado en el paso de registro.
     */
    public function requestCode(RegistrationCodeRequest $request): JsonResponse
    {
        try {
            // Reutiliza Turnstile para evitar abuso antes de enviar correos.
            $this->turnstileVerificationService->verify(
                $request->string('turnstile_token')->toString(),
                $request->ip()
            );

            $result = $this->registrationVerificationService->requestCode(
                $request->string('email')->toString(),
                false
            );

            return response()->json(['success' => true] + $result);
        } catch (AuthException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getCode());
        }
    }

    /**
     * Reenvía el código manteniendo la misma validación de seguridad.
     */
    public function resendCode(RegistrationCodeRequest $request): JsonResponse
    {
        try {
            // Reutiliza Turnstile y la misma política de enfriamiento del primer envío.
            $this->turnstileVerificationService->verify(
                $request->string('turnstile_token')->toString(),
                $request->ip()
            );

            $result = $this->registrationVerificationService->requestCode(
                $request->string('email')->toString(),
                true
            );

            return response()->json(['success' => true] + $result);
        } catch (AuthException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getCode());
        }
    }

    /**
     * Verifica el código y devuelve un token temporal para crear la cuenta.
     */
    public function verifyCode(RegistrationVerifyCodeRequest $request): JsonResponse
    {
        try {
            $data = $this->registrationVerificationService->verifyCode(
                $request->string('email')->toString(),
                $request->string('code')->toString()
            );

            return response()->json([
                'success' => true,
                'message' => 'Código validado.',
                'data' => $data,
            ]);
        } catch (AuthException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getCode());
        }
    }
}
