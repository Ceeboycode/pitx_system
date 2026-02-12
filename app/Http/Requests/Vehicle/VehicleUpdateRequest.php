<?php

namespace App\Http\Requests\Vehicle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')->id;

        return [
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate_number')->ignore($vehicleId),
            ],
            'body_number' => [
                'required',
                'string',
                'max:200',
                Rule::unique('vehicles', 'body_number')->ignore($vehicleId),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'company_id' => ['required', 'exists:companies,id'],
            'route_id' => ['required', 'exists:routes,id'],
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
        ];
    }
}
