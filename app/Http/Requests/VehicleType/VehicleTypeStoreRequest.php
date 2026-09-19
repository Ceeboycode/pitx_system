<?php

namespace App\Http\Requests\VehicleType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class VehicleTypeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_name' => ['required', 'string', 'max:100', 'unique:vehicle_types,type_name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'picture' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type_name' => Str::squish((string) $this->input('type_name')),
        ]);
    }
}
