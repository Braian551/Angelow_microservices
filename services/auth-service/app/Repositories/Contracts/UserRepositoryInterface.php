<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * Contract for User repository implementations.
 *
 * Defines the data access interface for user operations,
 * allowing for easy swapping of implementations (e.g., for testing).
 */
interface UserRepositoryInterface
{
    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by phone number.
     */
    public function findByPhone(string $phone): ?User;

    /**
     * Find a user by email or phone.
     */
    public function findByCredential(string $credential): ?User;

    /**
     * Find a user by ID.
     */
    public function findById(string $id): ?User;

    /**
     * Update last access timestamp.
     */
    public function updateLastAccess(User $user): void;

    /**
     * Check if an email is already registered.
     */
    public function emailExists(string $email): bool;
}
