<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for user login validation.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credential' => ['required', 'string'],
            'password'   => ['required', 'string'],
            'remember'   => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'credential.required' => 'El correo o teléfono es obligatorio',
            'password.required'   => 'La contraseña es obligatoria',
        ];
    }
}
