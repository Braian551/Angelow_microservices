<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la solicitud de código para confirmar el correo antes del registro.
 */
class RegistrationCodeRequest extends FormRequest
{
    /**
     * Autoriza el flujo público de pre-registro.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas mínimas: correo válido y verificación de seguridad vigente.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:100'],
            'turnstile_token' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * Mensajes visibles en español para el formulario de registro.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'turnstile_token.required' => 'Completa la verificación de seguridad para continuar.',
        ];
    }
}
