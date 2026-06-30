<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la entrada para solicitar o reenviar código de recuperación.
 *
 * Recibe un identifier que puede ser correo electrónico o teléfono.
 * La validación específica del formato se realiza en
 * PasswordRecoveryService::normalizeIdentifier().
 */
class PasswordRecoveryCodeRequest extends FormRequest
{
    /**
     * Autoriza la petición (pública).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas: identifier obligatorio, máximo 150 caracteres.
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:150'],
            'turnstile_token' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Debes ingresar el correo o teléfono asociado a tu cuenta.',
            'turnstile_token.required' => 'Completa la verificación de seguridad para continuar.',
        ];
    }
}
