<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fábrica de modelos User para el servicio de autenticación.
 *
 * Genera instancias falsas de User útiles en entornos de
 * desarrollo y pruebas unitarias/de integración.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Contraseña actual usada por la fábrica.
     * Se mantiene estática para reutilizar el mismo hash
     * en todas las instancias generadas durante una misma ejecución.
     */
    protected static ?string $password;

    /**
     * Define el estado por defecto del modelo.
     *
     * Genera nombre y email aleatorios, marca el email como verificado,
     * asigna una contraseña hasheada y crea un token de remember aleatorio.
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
     *
     * Útil para probar flujos donde se requiere verificación
     * de correo electrónico antes de permitir ciertas acciones.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
