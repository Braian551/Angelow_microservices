<?php

namespace App\DTOs;

/**
 * Data Transfer Object for user login.
 *
 * Encapsulates validated login credentials for transfer
 * between the Controller and Service layers.
 */
final readonly class LoginUserDTO
{
    public function __construct(
        public string $credential, // email or phone
        public string $password,
        public bool   $remember = false,
    ) {}

    /**
     * Create a DTO instance from a validated request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            credential: $data['credential'],
            password:   $data['password'],
            remember:   $data['remember'] ?? false,
        );
    }

    /**
     * Determine if the credential is an email address.
     */
    public function isEmail(): bool
    {
        return filter_var($this->credential, FILTER_VALIDATE_EMAIL) !== false;
    }
}
