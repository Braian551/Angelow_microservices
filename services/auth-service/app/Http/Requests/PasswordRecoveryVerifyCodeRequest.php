<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la entrada para verificar código de recuperación.
 */
class PasswordRecoveryVerifyCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'regex:/^[0-9]{4}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Debes ingresar el correo o teléfono asociado a tu cuenta.',
            'code.required' => 'Debes ingresar el código enviado a tu correo.',
            'code.regex' => 'El código debe tener 4 dígitos.',
        ];
    }
}
