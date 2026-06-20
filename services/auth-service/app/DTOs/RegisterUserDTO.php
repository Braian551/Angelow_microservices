<?php

namespace App\DTOs;

/**
 * DTO (Data Transfer Object) para registro de usuarios.
 *
 * Encapsula los datos validados del registro para transferencia
 * entre la capa de Controlador y la capa de Servicio (AuthService).
 * Es inmutable (readonly) para garantizar que los datos no se
 * modifiquen durante el flujo.
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
     * Crea una instancia del DTO a partir del array de datos validados.
     *
     * @param  array<string, mixed>  $data  Datos provenientes de RegisterRequest::validated()
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
