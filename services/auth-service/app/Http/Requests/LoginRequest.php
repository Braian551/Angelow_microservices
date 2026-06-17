<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para inicio de sesión.
 *
 * Valida que se envíen credential (email o teléfono) y password.
 * El campo credential es genérico (string) porque puede ser
 * un correo electrónico o un número de teléfono.
 */
class LoginRequest extends FormRequest
{
    /**
     * Autoriza la petición (pública).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el login.
     *
     * - credential: obligatorio, texto libre (correo o teléfono)
     * - password: obligatorio
     * - remember: opcional, booleano
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'string'],
            'password'   => ['required', 'string'],
            'remember'   => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'credential.required' => 'El correo o teléfono es obligatorio',
            'password.required'   => 'La contraseña es obligatoria',
        ];
    }
}
