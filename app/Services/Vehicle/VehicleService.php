<?php
namespace App\Services\Vehicle;
use App\Models\Vehicle;

class VehicleService
{
    public function createVehicle(array $data, int $userId): Vehicle
    {
        $data['created_by'] = $userId;

        return Vehicle::create($data);
    }

    public function updateVehicle(Vehicle $vehicle, array $data, int $userId): Vehicle
    {
        $data['updated_by'] = $userId;

        $vehicle->update($data);

        return $vehicle;
    }

    public function deleteVehicle(Vehicle $vehicle, int $userId): bool
    {
        $vehicle->deleted_by = $userId;
        $vehicle->save();

        return $vehicle->delete();
    }

    public function restoreVehicle(Vehicle $vehicle): bool
    {
        return $vehicle->restore();
    }

    public function forceDeleteVehicle(Vehicle $vehicle): bool
    {
        return $vehicle->forceDelete();
    }
    
}

