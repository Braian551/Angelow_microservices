<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * Contrato (interfaz) para implementaciones del repositorio de usuarios.
 *
 * Define las operaciones de acceso a datos para el dominio de usuarios,
 * permitiendo intercambiar implementaciones fácilmente (ej. Eloquent vs
 * Query Builder) sin afectar la capa de servicios.
 *
 * Implementación concreta: QueryBuilderUserRepository
 */
interface UserRepositoryInterface
{
    /**
     * Crea un nuevo usuario en la base de datos.
     */
    public function create(array $data): User;

    /**
     * Busca un usuario por su correo electrónico.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Busca un usuario por su número de teléfono.
     */
    public function findByPhone(string $phone): ?User;

    /**
     * Busca un usuario por correo electrónico o teléfono.
     */
    public function findByCredential(string $credential): ?User;

    /**
     * Busca un usuario por su ID.
     */
    public function findById(string $id): ?User;

    /**
     * Actualiza la marca de tiempo del último acceso del usuario.
     */
    public function updateLastAccess(User $user): void;

    /**
     * Verifica si un correo electrónico ya está registrado.
     */
    public function emailExists(string $email): bool;
}
