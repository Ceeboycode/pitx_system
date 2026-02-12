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

    public function deleteVehicle(Vehicle $vehicle, int $userId): void
    {
        $vehicle->update(['deleted_by' => $userId]);
        
        $vehicle->delete();
    }
}

