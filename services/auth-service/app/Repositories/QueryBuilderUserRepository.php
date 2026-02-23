<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Query Builder implementation of the User repository.
 *
 * Uses Laravel's DB facade instead of Eloquent ORM.
 */
class QueryBuilderUserRepository implements UserRepositoryInterface
{
    /**
     * Hydrate a generic database record into a User model.
     * This is required because Laravel Sanctum (`createToken`) 
     * and relationships require an Eloquent Model instance.
     */
    private function hydrate(?object $record): ?User
    {
        if (!$record) {
            return null;
        }

        $user = new User((array) $record);
        $user->exists = true; // Tell Eloquent this model exists in DB
        $user->id = $record->id; // Ensure the ID is explicitly set
        return $user;
    }

    public function create(array $data): User
    {
        // Hash the password if provided, since we bypass Eloquent model casts
        if (isset($data['password']) && !password_get_info($data['password'])['algo']) {
            $data['password'] = bcrypt($data['password']);
        }
        
        // created_at / updated_at are not auto-filled by Query Builder by default
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('users')->insert($data);

        return $this->findById($data['id']);
    }

    public function findByEmail(string $email): ?User
    {
        $record = DB::table('users')->where('email', $email)->first();
        return $this->hydrate($record);
    }

    public function findByPhone(string $phone): ?User
    {
        $record = DB::table('users')->where('phone', $phone)->first();
        return $this->hydrate($record);
    }

    public function findByCredential(string $credential): ?User
    {
        $isEmail = filter_var($credential, FILTER_VALIDATE_EMAIL) !== false;

        $record = DB::table('users')
            ->where(function ($query) use ($credential, $isEmail) {
                if ($isEmail) {
                    $query->where('email', $credential);
                } else {
                    $query->where('phone', $credential);
                }
            })
            ->first();

        return $this->hydrate($record);
    }

    public function findById(string $id): ?User
    {
        $record = DB::table('users')->where('id', $id)->first();
        return $this->hydrate($record);
    }

    public function updateLastAccess(User $user): void
    {
        DB::table('users')
            ->where('id', $user->id)
            ->update(['last_access' => now()]);
            
        $user->last_access = now();
    }

    public function emailExists(string $email): bool
    {
        return DB::table('users')->where('email', $email)->exists();
    }
}
