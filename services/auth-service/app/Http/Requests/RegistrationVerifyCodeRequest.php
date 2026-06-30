<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el código que confirma el correo durante el registro.
 */
class RegistrationVerifyCodeRequest extends FormRequest
{
    /**
     * Autoriza la validación pública del código de registro.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas: correo válido y código exacto de cuatro dígitos.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{4}$/'],
        ];
    }

    /**
     * Mensajes claros para validación en tiempo real y respuesta API.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'code.required' => 'Debes ingresar el código enviado a tu correo.',
            'code.regex' => 'El código debe tener 4 dígitos.',
        ];
    }
}
