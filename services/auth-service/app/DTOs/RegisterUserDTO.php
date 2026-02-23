<?php

namespace App\DTOs;

/**
 * Data Transfer Object for user registration.
 *
 * Encapsulates validated registration data for transfer
 * between the Controller and Service layers.
 */
final readonly class RegisterUserDTO
{
    public function __construct(
        public string  $name,
        public string  $email,
        public ?string $phone,
        public string  $password,
    ) {}

    /**
     * Create a DTO instance from a validated request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:     $data['name'],
            email:    $data['email'],
            phone:    $data['phone'] ?? null,
            password: $data['password'],
        );
    }
}
