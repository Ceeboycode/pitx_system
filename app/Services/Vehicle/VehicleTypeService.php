<?php

namespace App\Services\Vehicle;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Validation\ValidationException;

class VehicleTypeService
{
    public function createVehicleType(array $data): VehicleType
    {
        $data['created_by'] = auth()->id();

        return VehicleType::create($data);
    }

    public function updateVehicleType(VehicleType $vehicleType, array $data): VehicleType
    {
        $data['updated_by'] = auth()->id();

        $vehicleType->update($data);

        return $vehicleType;
    }

    public function deleteVehicleType(VehicleType $vehicleType): void
    {
        if (Vehicle::withTrashed()->whereBelongsTo($vehicleType)->exists()) {
            throw ValidationException::withMessages([
                'vehicle_type' => 'This vehicle type is assigned to one or more vehicles and cannot be deleted. Deactivate it instead.',
            ]);
        }

        $vehicleType->delete();
    }
}
