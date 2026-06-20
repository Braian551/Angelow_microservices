<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para inicio de sesión con Google (Firebase).
 *
 * Valida que se envíe el id_token (token ID de Firebase) para
 * autenticar al usuario mediante su cuenta de Google.
 */
class GoogleLoginRequest extends FormRequest
{
    /**
     * Autoriza la petición (pública).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación: id_token obligatorio.
     */
    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'id_token.required' => 'El token de Google es obligatorio',
        ];
    }
}

