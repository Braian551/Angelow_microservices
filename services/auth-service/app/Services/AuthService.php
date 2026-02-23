<?php

namespace App\Services;

use App\DTOs\LoginUserDTO;
use App\DTOs\RegisterUserDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
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
     * Revoke all tokens for a user (logout).
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
