<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la entrada para restablecer la contraseña.
 *
 * Recibe session_token (emitido tras verificar el código),
 * password y password_confirmation. La validación de fortaleza
 * de contraseña (8-64 caracteres) se hace tanto aquí como en
 * el servicio para doble seguridad.
 */
class PasswordRecoveryResetRequest extends FormRequest
{
    /**
     * Autoriza la petición (pública, pero requiere session_token válido).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas: session_token obligatorio (mín 32 chars),
     * password entre 8 y 64 caracteres con confirmación.
     */
    public function rules(): array
    {
        return [
            'session_token' => ['required', 'string', 'min:32'],
            'password' => ['required', 'string', 'min:8', 'max:64', 'confirmed'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
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
