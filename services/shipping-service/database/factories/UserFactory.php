<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fábrica de modelos User para pruebas.
 *
 * Genera instancias de App\Models\User con datos realistas
 * para usar en tests y desarrollo local.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Contraseña actual usada por la fábrica.
     * Se reutiliza entre llamadas para evitar re-hashear.
     */
    protected static ?string $password;

    /**
     * Define el estado predeterminado del modelo.
     *
     * Genera nombre, email único verificado, contraseña
     * predeterminada ('password') y token de recordatorio.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indica que el email del modelo no debe estar verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
