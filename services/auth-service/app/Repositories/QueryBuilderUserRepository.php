<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Implementación del repositorio de usuarios con Query Builder.
 *
 * Usa la fachada DB de Laravel en lugar de Eloquent ORM.
 * Se eligió Query Builder porque durante la migración legacy se
 * necesita control explícito sobre las consultas y el hasheo
 * de contraseñas (el cast "hashed" de Eloquent se omite).
 *
 * Nota: El método hydrate() convierte registros stdClass a modelos
 * Eloquent porque Sanctum (createToken) y las relaciones requieren
 * una instancia de Model.
 */
class QueryBuilderUserRepository implements UserRepositoryInterface
{
    /**
     * Hidrata un registro genérico de BD en un modelo User de Eloquent.
     *
     * Necesario porque Sanctum (createToken) y las relaciones requieren
     * una instancia de Model de Eloquent, no un stdClass.
     * Marca el modelo como "exists" para que Eloquent sepa que ya
     * está persistido y asigna explícitamente el ID.
     */
    private function hydrate(?object $record): ?User
    {
        if (!$record) {
            return null;
        }

        $user = new User((array) $record);
        $user->exists = true; // Indica a Eloquent que este modelo existe en BD
        $user->id = $record->id; // Asigna el ID explícitamente
        return $user;
    }

    /**
     * Crea un nuevo usuario en la tabla users.
     *
     * Como se usa Query Builder (no Eloquent), el hash de la contraseña
     * y los timestamps se manejan manualmente.
     */
    public function create(array $data): User
    {
        // Hashea la contraseña manualmente porque se omite el cast de Eloquent
        if (isset($data['password']) && !password_get_info($data['password'])['algo']) {
            $data['password'] = bcrypt($data['password']);
        }

        // Query Builder no asigna created_at/updated_at automáticamente
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('users')->insert($data);

        return $this->findById($data['id']);
    }

    public function findByEmail(string $email): ?User
    {
        // Los proveedores externos (p. ej. Google/Firebase) pueden variar
        // las mayúsculas del correo. La identidad de correo debe ser única
        // sin depender de esa diferencia para evitar crear otra cuenta con
        // rol customer durante el inicio de sesión.
        $normalizedEmail = strtolower(trim($email));
        $record = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();
        return $this->hydrate($record);
    }

    public function findByPhone(string $phone): ?User
    {
        $record = DB::table('users')->where('phone', $phone)->first();
        return $this->hydrate($record);
    }

    /**
     * Busca usuario por email o teléfono según el formato de la credencial.
     */
    public function findByCredential(string $credential): ?User
    {
        $normalizedCredential = trim($credential);
        $isEmail = filter_var($normalizedCredential, FILTER_VALIDATE_EMAIL) !== false;

        $record = DB::table('users')
            ->where(function ($query) use ($normalizedCredential, $isEmail) {
                if ($isEmail) {
                    $query->whereRaw('LOWER(email) = ?', [strtolower($normalizedCredential)]);
                } else {
                    $query->where('phone', $normalizedCredential);
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

    /**
     * Actualiza el timestamp de último acceso del usuario.
     */
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
