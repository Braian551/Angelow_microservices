<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la entrada para restablecer contraseña con token de recuperación.
 */
class PasswordRecoveryResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_token' => ['required', 'string', 'min:32'],
            'password' => ['required', 'string', 'min:8', 'max:64', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_token.required' => 'La sesión de recuperación no es válida o expiró.',
            'password.required' => 'Debes ingresar una nueva contraseña.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La nueva contraseña no puede exceder 64 caracteres.',
            'password.confirmed' => 'Las contraseñas ingresadas no coinciden.',
        ];
    }
}
