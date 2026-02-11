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
}

