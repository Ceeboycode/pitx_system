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
            'plate_number' => 'required|string|max:6|unique:vehicles,plate_number',
            'body_number' => 'required|string|max:200|unique:vehicles,body_number',
            'capacity' => 'required|integer|min:1',
            'company_id' => 'required|exists:companies,id',
            'route_id' => 'required|exists:routes,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.required' => 'Plate number is required.',
            'plate_number.unique' => 'This plate number already exists.',
            'plate_number.max' => 'Plate number must not exceed 6 characters.',

            'body_number.required' => 'Body number is required.',
            'body_number.unique' => 'This body number already exists.',
            'body_number.max' => 'Body number must not exceed 200 characters.',

            'capacity.required' => 'Capacity is required.',
            'capacity.integer' => 'Capacity must be a valid number.',
            'capacity.min' => 'Capacity must be at least 1.',

            'company_id.required' => 'Please select a company.',
            'company_id.exists' => 'Selected company is invalid.',

            'route_id.required' => 'Please select a route.',
            'route_id.exists' => 'Selected route is invalid.',

            'vehicle_type_id.required' => 'Please select a vehicle type.',
            'vehicle_type_id.exists' => 'Selected vehicle type is invalid.',
        ];
    }
}
