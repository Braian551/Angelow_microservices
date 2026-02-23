<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for user registration validation.
 *
 * All validation rules are centralized here to keep
 * the controller clean and focused on orchestration.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:100'],
            'email'    => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'password' => ['required', 'string', 'min:6', 'max:20', 'confirmed'],
            'terms'    => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre es obligatorio',
            'name.min'           => 'El nombre debe tener al menos 2 caracteres',
            'email.required'     => 'El correo electrónico es obligatorio',
            'email.email'        => 'El correo electrónico no es válido',
            'email.unique'       => 'Este correo ya está registrado',
            'phone.regex'        => 'El teléfono debe tener entre 10 y 15 dígitos',
            'password.required'  => 'La contraseña es obligatoria',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres',
            'password.max'       => 'La contraseña no puede tener más de 20 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'terms.required'     => 'Debes aceptar los términos y condiciones',
            'terms.accepted'     => 'Debes aceptar los términos y condiciones',
        ];
    }
}
