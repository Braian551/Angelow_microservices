<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CourierRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[\pL]+(?:[ .\'-][\pL]+)+$/u',
            ],
            'email' => ['required', 'email:rfc', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^3[0-9]{9}$/'],
            'password' => [
                'required',
                'string',
                'max:64',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'registration_token' => ['required', 'string', 'size:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Escribe nombre y apellido usando solo letras.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'phone.regex' => 'Ingresa un celular colombiano válido de 10 dígitos.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'registration_token.size' => 'La verificación del correo no es válida.',
        ];
    }
}
