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

    public function archiveVehicleType(VehicleType $vehicleType): void
    {
        if (Vehicle::whereBelongsTo($vehicleType)->exists()) {
            throw ValidationException::withMessages([
                'vehicle_type' => 'This vehicle type is assigned to one or more vehicles and cannot be archived. Deactivate it instead.',
            ]);
        }

        $vehicleType->update(['deleted_by' => auth()->id()]);
        $vehicleType->delete();
    }

    public function restoreVehicleType(VehicleType $vehicleType): void
    {
        $vehicleType->update(['deleted_by' => null, 'updated_by' => auth()->id()]);
        $vehicleType->restore();
    }

    public function forceDeleteVehicleType(VehicleType $vehicleType): void
    {
        if (Vehicle::withTrashed()->whereBelongsTo($vehicleType)->exists()) {
            throw ValidationException::withMessages([
                'vehicle_type' => 'This vehicle type is assigned to one or more vehicles and cannot be permanently deleted.',
            ]);
        }

        $vehicleType->forceDelete();
    }
}
