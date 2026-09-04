<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\LoginUserDTO;
use App\DTOs\RegisterUserDTO;
use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourierCodeRequest;
use App\Http\Requests\CourierLoginRequest;
use App\Http\Requests\CourierRegisterRequest;
use App\Http\Requests\CourierVerifyCodeRequest;
use App\Http\Requests\GoogleLoginRequest;
use App\Services\AuthService;
use App\Services\RegistrationVerificationService;
use Illuminate\Http\JsonResponse;

class CourierAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RegistrationVerificationService $verificationService,
    ) {}

    public function requestCode(CourierCodeRequest $request): JsonResponse
    {
        return $this->sendCode($request, false);
    }

    public function resendCode(CourierCodeRequest $request): JsonResponse
    {
        return $this->sendCode($request, true);
    }

    public function verifyCode(CourierVerifyCodeRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Correo verificado.',
                'data' => $this->verificationService->verifyCourierCode(
                    $request->string('email')->toString(),
                    $request->string('code')->toString(),
                ),
            ]);
        } catch (AuthException $exception) {
            return $this->authError($exception);
        }
    }

    public function login(CourierLoginRequest $request): JsonResponse
    {
        try {
            $email = $request->string('email')->toString();
            $this->verificationService->assertCourierToken(
                $email,
                $request->string('verification_token')->toString(),
                ['password'],
            );

            $result = $this->authService->loginCourier(new LoginUserDTO(
                credential: $email,
                password: $request->string('password')->toString(),
            ));
            $this->verificationService->consumeCourierToken(
                $email,
                $request->string('verification_token')->toString(),
            );

            return $this->sessionResponse($result, 'Inicio de sesión exitoso.');
        } catch (AuthException $exception) {
            return $this->authError($exception);
        }
    }

    public function register(CourierRegisterRequest $request): JsonResponse
    {
        try {
            $email = $request->string('email')->toString();
            $this->verificationService->assertCourierToken(
                $email,
                $request->string('registration_token')->toString(),
                ['register'],
            );

            $result = $this->authService->registerCourier(RegisterUserDTO::fromArray($request->validated()));
            $this->verificationService->consumeCourierToken(
                $email,
                $request->string('registration_token')->toString(),
            );

            return $this->sessionResponse($result, 'Cuenta de repartidor creada.', 201);
        } catch (AuthException $exception) {
            return $this->authError($exception);
        }
    }

    public function google(GoogleLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginCourierWithGoogleToken(
                $request->string('id_token')->toString(),
            );

            return $this->sessionResponse($result, 'Cuenta de Google verificada.');
        } catch (AuthException $exception) {
            return $this->authError($exception);
        }
    }

    private function sendCode(CourierCodeRequest $request, bool $isResend): JsonResponse
    {
        try {
            $result = $this->verificationService->requestCourierCode(
                $request->string('email')->toString(),
                $isResend,
            );

            return response()->json(['success' => true] + $result);
        } catch (AuthException $exception) {
            return $this->authError($exception);
        }
    }

    private function sessionResponse(array $result, string $message, int $status = 200): JsonResponse
    {
        $user = $result['user'];

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'image' => $user->image,
                    'role' => $user->role,
                ],
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'requires_profile' => (bool) ($result['requires_profile'] ?? false),
            ],
        ], $status);
    }

    private function authError(AuthException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], $exception->getCode());
    }
}
