<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CourierProfileRequest extends FormRequest
{
    private const MOTORIZED_TYPES = ['motorcycle', 'car', 'truck'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isMotorized = in_array($this->input('vehicle.type'), self::MOTORIZED_TYPES, true);

        return [
            'document_type' => ['required', Rule::in(['cc', 'ce', 'passport', 'ppt'])],
            'document_number' => ['required', 'string', 'min:5', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'birth_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . now()->subYears(100)->toDateString(),
                'before_or_equal:' . now()->subYears(18)->toDateString(),
            ],
            'phone' => ['required', 'string', 'regex:/^3[0-9]{9}$/'],
            'address' => ['required', 'string', 'min:5', 'max:180'],
            'terms_version' => ['required', 'string', 'max:30'],
            'accept_terms' => ['required', 'accepted'],
            'vehicle.type' => ['required', Rule::in(['foot', 'bicycle', ...self::MOTORIZED_TYPES])],
            'vehicle.make_id' => [Rule::requiredIf($isMotorized), 'nullable', 'string', 'max:40'],
            'vehicle.make_name' => [Rule::requiredIf($isMotorized), 'nullable', 'string', 'max:100'],
            'vehicle.model_id' => [Rule::requiredIf($isMotorized), 'nullable', 'string', 'max:40'],
            'vehicle.model_name' => [Rule::requiredIf($isMotorized), 'nullable', 'string', 'max:100'],
            'vehicle.color_name' => [Rule::requiredIf($isMotorized), 'nullable', 'string', 'max:60'],
            'vehicle.color_hex' => [
                Rule::requiredIf($isMotorized),
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'vehicle.year' => [
                Rule::requiredIf($isMotorized),
                'nullable',
                'integer',
                'min:1950',
                'max:' . (now()->year + 1),
            ],
            'vehicle.plate' => [
                Rule::requiredIf($isMotorized),
                'nullable',
                'string',
                'regex:/^[A-Z0-9-]{5,12}$/',
            ],
            'vehicle.ownership_type' => [
                Rule::requiredIf($isMotorized),
                'nullable',
                Rule::in(['owned', 'rented', 'borrowed']),
            ],
            'documents' => [
                'nullable',
                'array:identity_front,identity_back,profile_photo,driving_license,vehicle_registration,soat,technical_inspection',
            ],
            'documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'document_expiries' => [
                'nullable',
                'array:identity_front,identity_back,profile_photo,driving_license,vehicle_registration,soat,technical_inspection',
            ],
            'document_expiries.*' => ['nullable', 'date_format:Y-m-d'],
            'technical_inspection_not_applicable' => ['nullable', 'boolean'],
            'city' => ['prohibited'],
            'emergency_contact_name' => ['prohibited'],
            'emergency_contact_phone' => ['prohibited'],
            'work_modality' => ['prohibited'],
            'eps_name' => ['prohibited'],
            'pension_fund' => ['prohibited'],
            'arl_status' => ['prohibited'],
            'accept_data_policy' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $vehicle = (array) $this->input('vehicle', []);
        $vehicle['plate'] = mb_strtoupper(trim((string) ($vehicle['plate'] ?? '')));

        $this->merge([
            'document_type' => mb_strtolower(trim((string) $this->input('document_type'))),
            'document_number' => mb_strtoupper(trim((string) $this->input('document_number'))),
            'phone' => trim((string) $this->input('phone')),
            'address' => preg_replace('/\s+/u', ' ', trim((string) $this->input('address'))),
            'vehicle' => $vehicle,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                in_array($this->string('document_type')->toString(), ['cc', 'ce'], true)
                && !preg_match('/^[0-9]{6,10}$/', $this->string('document_number')->toString())
            ) {
                $validator->errors()->add(
                    'document_number',
                    'La cédula debe tener entre 6 y 10 dígitos.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'document_number.regex' => 'El documento solo puede contener letras, números y guiones.',
            'birth_date.date_format' => 'Selecciona una fecha de nacimiento válida.',
            'birth_date.after_or_equal' => 'Revisa la fecha de nacimiento.',
            'birth_date.before_or_equal' => 'Debes ser mayor de 18 años.',
            'phone.regex' => 'Ingresa un celular colombiano válido de 10 dígitos.',
            'address.min' => 'Ingresa una dirección de residencia válida.',
            'accept_terms.accepted' => 'Debes aceptar los términos y condiciones.',
            'vehicle.*.required' => 'Completa todos los datos del vehículo.',
            'vehicle.plate.regex' => 'Ingresa una placa válida.',
            'documents.array' => 'La solicitud contiene un tipo de documento no permitido.',
            'documents.*.mimes' => 'Cada documento debe ser JPG, PNG o PDF.',
            'documents.*.max' => 'Cada documento puede pesar máximo 8 MB.',
            '*.prohibited' => 'La solicitud contiene campos que ya no hacen parte del registro.',
        ];
    }
}
