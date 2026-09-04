<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourierVerifyCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:100'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'code' => trim((string) $this->input('code')),
        ]);
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Ingresa un correo electrónico válido.',
            'code.required' => 'Debes ingresar el código enviado a tu correo.',
            'code.regex' => 'El código debe tener 6 dígitos.',
        ];
    }
}
