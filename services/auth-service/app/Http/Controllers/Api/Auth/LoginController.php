<?php

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\LoginUserDTO;
use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Services\LoginAttemptProtectionService;
use App\Services\TurnstileVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de autenticación (login/logout/Google/me).
 *
 * Agrupa los endpoints públicos y protegidos de sesión.
 * Delega la lógica de negocio a AuthService y normaliza
 * las rutas de imágenes para el frontend SPA.
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly TurnstileVerificationService $turnstileVerificationService,
        private readonly LoginAttemptProtectionService $loginAttemptProtectionService,
    ) {}

    /**
     * Autentica un usuario con correo/teléfono y contraseña.
     *
     * POST /api/auth/login
     * Recibe: credential (email o phone), password
     * Retorna: datos del usuario + token Bearer
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credential = $request->string('credential')->toString();
        $ipAddress = (string) $request->ip();
        $protectionStatus = $this->loginAttemptProtectionService->status($credential, $ipAddress);

        if ($protectionStatus['is_blocked']) {
            return response()->json([
                'success' => false,
                'message' => 'Por seguridad, espera unos minutos antes de volver a intentarlo.',
                'captcha_required' => true,
                'blocked_until' => $protectionStatus['blocked_until'],
            ], 429);
        }

        try {
            if ($protectionStatus['captcha_required']) {
                // El backend decide cuándo exigir verificación; la SPA solo refleja este estado.
                $this->turnstileVerificationService->verify(
                    $request->string('turnstile_token')->toString(),
                    $request->ip()
                );
            }

            $dto = LoginUserDTO::fromArray($request->validated());
            $result = $this->authService->login($dto);
            $this->loginAttemptProtectionService->clear($credential, $ipAddress);

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'captcha_required' => false,
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
            // Solo los fallos reales de credenciales aumentan el contador; la verificación incompleta no debe bloquear al usuario.
            $shouldRecordFailure = $e->getCode() === 401;
            $failureStatus = $shouldRecordFailure
                ? $this->loginAttemptProtectionService->recordFailure($credential, $ipAddress)
                : $this->loginAttemptProtectionService->status($credential, $ipAddress);

            if ($failureStatus['is_blocked']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por seguridad, espera unos minutos antes de volver a intentarlo.',
                    'captcha_required' => true,
                    'blocked_until' => $failureStatus['blocked_until'],
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'captcha_required' => $failureStatus['captcha_required'],
                'blocked_until' => $failureStatus['blocked_until'],
            ], $e->getCode());
        }
    }

    /**
     * Autentica un usuario mediante token ID de Google (Firebase).
     *
     * POST /api/auth/google
     * Recibe: id_token (Firebase ID token)
     * Si el email no existe en BD, crea cuenta automáticamente.
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
     * Cierra la sesión revocando todos los tokens del usuario.
     *
     * POST /api/auth/logout (requiere autenticación)
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
     * Devuelve el perfil del usuario autenticado.
     *
     * GET /api/auth/me (requiere autenticación)
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
     *
     * Si solo hay nombre de archivo (legacy), resuelve como uploads/users/.
     * Si ya tiene ruta, la limpia y elimina backslash. Si está vacío,
     * retorna default-avatar.png.
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

