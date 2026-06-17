<?php

namespace App\Services;

use App\DTOs\LoginUserDTO;
use App\DTOs\RegisterUserDTO;
use App\Exceptions\AuthException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Servicio principal de autenticación.
 *
 * Contiene toda la lógica de negocio para registro, inicio de sesión,
 * autenticación con Google (Firebase) y cierre de sesión.
 * Delega el acceso a datos al UserRepositoryInterface y usa
 * WelcomeEmailService para notificaciones post-registro.
 */
class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly WelcomeEmailService $welcomeEmailService,
    ) {}

    /**
     * Registra un nuevo usuario en el sistema.
     *
     * Valida que el email no esté duplicado, genera un ID único
     * compatible con el sistema legacy (uniqid), persiste el usuario
     * vía repositorio y crea un token Sanctum.
     * El envío del email de bienvenida no debe impedir el registro
     * si falla temporalmente.
     *
     * @throws AuthException si el email ya existe
     * @return array{user: User, token: string}
     */
    public function register(RegisterUserDTO $dto): array
    {
        // Verifica email duplicado antes de crear
        if ($this->userRepository->emailExists($dto->email)) {
            throw new AuthException(
                'Este correo ya está registrado',
                409
            );
        }

        // Genera ID único compatible con el sistema legacy (uniqid)
        $userId = uniqid();

        $user = $this->userRepository->create([
            'id'       => $userId,
            'name'     => $dto->name,
            'email'    => $dto->email,
            'phone'    => $dto->phone,
            'password' => $dto->password, // El cast "hashed" del modelo se encarga del hash
            'role'     => 'customer',
        ]);

        // Genera token de acceso API
        $token = $user->createToken('auth-token')->plainTextToken;

        // El registro no debe fallar si el correo presenta un problema temporal.
        $this->welcomeEmailService->send((string) $user->email, (string) $user->name);

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Autentica un usuario por correo/teléfono y contraseña.
     *
     * Busca al usuario por la credencial (email o teléfono),
     * verifica que no esté bloqueado, valida la contraseña,
     * actualiza el último acceso y genera un token Sanctum.
     *
     * @throws AuthException si las credenciales son inválidas o el usuario está bloqueado
     * @return array{user: User, token: string}
     */
    public function login(LoginUserDTO $dto): array
    {
        $user = $this->userRepository->findByCredential($dto->credential);

        if (!$user) {
            throw new AuthException(
                'Credenciales incorrectas',
                401
            );
        }

        if ($user->isBlocked()) {
            throw new AuthException(
                'Tu cuenta ha sido bloqueada. Por favor, contacta al administrador.',
                403
            );
        }

        if (!Hash::check($dto->password, $user->password)) {
            throw new AuthException(
                'Credenciales incorrectas',
                401
            );
        }

        // Actualiza la fecha del último acceso
        $this->userRepository->updateLastAccess($user);

        // Genera token de acceso API
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Autentica o registra un usuario mediante token ID de Google (Firebase).
     *
     * Valida el token contra Firebase Identity Toolkit. Si el email
     * ya existe en BD, inicia sesión. Si no existe, crea una cuenta
     * nueva con los datos de Google (nombre, teléfono si aplica,
     * contraseña aleatoria de 40 caracteres).
     *
     * @throws AuthException si Firebase no valida el token o el email no está verificado
     * @return array{user: User, token: string}
     */
    public function loginWithGoogleToken(string $idToken): array
    {
        $apiKey = (string) config('services.firebase.web_api_key');

        if ($apiKey === '') {
            throw new AuthException(
                'La configuración de Firebase no está completa',
                500
            );
        }

        // Valida el token ID contra Firebase
        $response = Http::timeout(10)->post(
            "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}",
            ['idToken' => $idToken]
        );

        if (!$response->successful()) {
            throw new AuthException(
                'No se pudo validar la cuenta de Google',
                401
            );
        }

        $firebaseUser = $response->json('users.0');

        if (!is_array($firebaseUser)) {
            throw new AuthException(
                'La cuenta de Google no es válida',
                401
            );
        }

        $email = strtolower(trim((string) ($firebaseUser['email'] ?? '')));
        $isEmailVerified = (bool) ($firebaseUser['emailVerified'] ?? false);

        if ($email === '' || !$isEmailVerified) {
            throw new AuthException(
                'Google no devolvió un correo verificado',
                401
            );
        }

        $user = $this->userRepository->findByEmail($email);

        $createdFromGoogle = false;

        if (!$user) {
            // Crea cuenta nueva a partir de los datos de Google
            $displayName = trim((string) ($firebaseUser['displayName'] ?? ''));
            $name = $displayName !== '' ? $displayName : explode('@', $email)[0];
            $rawPhone = trim((string) ($firebaseUser['phoneNumber'] ?? ''));
            $phoneDigits = preg_replace('/\D+/', '', $rawPhone ?? '');
            $phone = ($phoneDigits !== '' && strlen($phoneDigits) >= 10 && strlen($phoneDigits) <= 15)
                ? $phoneDigits
                : null;

            $user = $this->userRepository->create([
                'id'       => uniqid(),
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => Str::random(40),
                'role'     => 'customer',
            ]);

            $createdFromGoogle = true;
        } else {
            if ($user->isBlocked()) {
                throw new AuthException(
                    'Tu cuenta ha sido bloqueada. Por favor, contacta al administrador.',
                    403
                );
            }

            $this->userRepository->updateLastAccess($user);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Solo envía bienvenida en la primera creación vía Google
        if ($createdFromGoogle) {
            $this->welcomeEmailService->send((string) $user->email, (string) $user->name);
        }

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoca todos los tokens del usuario (cierre de sesión completo).
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}

