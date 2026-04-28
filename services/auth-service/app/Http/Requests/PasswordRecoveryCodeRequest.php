<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la entrada para solicitar/reenviar codigo de recuperacion.
 */
class PasswordRecoveryCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Debes ingresar el correo o telefono asociado a tu cuenta.',
        ];
    }
}

