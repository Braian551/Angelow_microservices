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
 * Authentication Service
 *
 * Contains all business logic for user registration and login.
 * Delegates data access to the UserRepository.
 */
class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Register a new user.
     *
     * @throws \App\Exceptions\AuthException
     * @return array{user: User, token: string}
     */
    public function register(RegisterUserDTO $dto): array
    {
        // Check for duplicate email
        if ($this->userRepository->emailExists($dto->email)) {
            throw new \App\Exceptions\AuthException(
                'Este correo ya está registrado',
                409
            );
        }

        // Generate a unique ID compatible with the legacy system
        $userId = uniqid();

        $user = $this->userRepository->create([
            'id'       => $userId,
            'name'     => $dto->name,
            'email'    => $dto->email,
            'phone'    => $dto->phone,
            'password' => $dto->password, // Model casts handle hashing
            'role'     => 'customer',
        ]);

        // Generate API token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate a user.
     *
     * @throws \App\Exceptions\AuthException
     * @return array{user: User, token: string}
     */
    public function login(LoginUserDTO $dto): array
    {
        $user = $this->userRepository->findByCredential($dto->credential);

        if (!$user) {
            throw new \App\Exceptions\AuthException(
                'Credenciales incorrectas',
                401
            );
        }

        if ($user->isBlocked()) {
            throw new \App\Exceptions\AuthException(
                'Tu cuenta ha sido bloqueada. Por favor, contacta al administrador.',
                403
            );
        }

        if (!Hash::check($dto->password, $user->password)) {
            throw new \App\Exceptions\AuthException(
                'Credenciales incorrectas',
                401
            );
        }

        // Update last access
        $this->userRepository->updateLastAccess($user);

        // Generate API token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate a user with a Firebase Google ID token.
     *
     * @throws \App\Exceptions\AuthException
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

        if (!$user) {
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

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke all tokens for a user (logout).
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}

