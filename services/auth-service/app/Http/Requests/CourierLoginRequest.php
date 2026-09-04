<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourierLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:64'],
            'verification_token' => ['required', 'string', 'size:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'verification_token.size' => 'La verificación del correo no es válida.',
        ];
    }
}
