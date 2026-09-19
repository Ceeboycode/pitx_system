<?php

namespace App\Http\Requests\Vehicle;

use App\Models\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VehicleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleParam = $this->route('vehicle');
        $vehicleId = $vehicleParam instanceof \App\Models\Vehicle ? $vehicleParam->id : $vehicleParam;

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $vehicle = $this->route('vehicle');
            $currentTypeId = $vehicle instanceof \App\Models\Vehicle ? $vehicle->vehicle_type_id : null;
            $selectedType = VehicleType::find($this->integer('vehicle_type_id'));

            if ($selectedType && ! $selectedType->is_active && (int) $selectedType->id !== (int) $currentTypeId) {
                $validator->errors()->add('vehicle_type_id', 'Inactive vehicle types cannot be assigned.');
            }
        }];
    }
}
