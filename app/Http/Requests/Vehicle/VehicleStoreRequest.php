<?php

namespace App\Http\Requests\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class VehicleStoreRequest extends FormRequest
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
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'body_number' => 'required|string|max:200|unique:vehicles,body_number',
            'capacity' => 'required|integer|min:1',
            'company_id' => 'required|exists:companies,id',
            'route_id' => 'required|exists:routes,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ];
    }
}
