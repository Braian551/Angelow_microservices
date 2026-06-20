<?php

namespace App\DTOs;

/**
 * DTO (Data Transfer Object) para inicio de sesión.
 *
 * Encapsula las credenciales validadas (correo/teléfono y contraseña)
 * para transferencia entre el Controlador y AuthService.
 * Incluye un helper isEmail() para determinar el tipo de credencial.
 */
final readonly class LoginUserDTO
{
    public function __construct(
        public string $credential, // email o teléfono
        public string $password,
        public bool   $remember = false,
    ) {}

    /**
     * Crea una instancia del DTO a partir del array de datos validados.
     *
     * @param  array<string, mixed>  $data  Datos provenientes de LoginRequest::validated()
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
     * Determina si la credencial ingresada es un correo electrónico.
     */
    public function isEmail(): bool
    {
        return filter_var($this->credential, FILTER_VALIDATE_EMAIL) !== false;
    }
}
