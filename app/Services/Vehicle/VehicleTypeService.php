<?php
namespace App\Services\Vehicle;
use App\Models\VehicleType;

class VehicleTypeService
{
    public function createVehicleType(array $data): VehicleType
    {
        $data['created_by'] = auth()->id();

        return VehicleType::create($data);
    }

    public function updateVehicleType(VehicleType $vehicleType, array $data): VehicleType {
        $data['updated_by'] = auth()->id();

        $vehicleType->update($data);

        return $vehicleType;
    }

    public function deleteVehicleType(VehicleType $vehicleType): void
    {
        $vehicleType->delete();
    }
}
