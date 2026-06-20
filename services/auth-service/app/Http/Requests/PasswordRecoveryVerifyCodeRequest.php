<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la entrada para verificar el código de recuperación.
 *
 * Recibe identifier (email/teléfono) y code (4 dígitos exactos).
 * El formato del código se valida con regex en el request para
 * evitar procesar códigos inválidos en la capa de servicio.
 */
class PasswordRecoveryVerifyCodeRequest extends FormRequest
{
    /**
     * Autoriza la petición (pública).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas: identifier obligatorio, code de 4 dígitos exactos.
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'regex:/^[0-9]{4}$/'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Debes ingresar el correo o teléfono asociado a tu cuenta.',
            'code.required' => 'Debes ingresar el código enviado a tu correo.',
            'code.regex' => 'El código debe tener 4 dígitos.',
        ];
    }
}
