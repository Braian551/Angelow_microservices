<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for Firebase Google login validation.
 */
class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_token.required' => 'El token de Google es obligatorio',
        ];
    }
}

