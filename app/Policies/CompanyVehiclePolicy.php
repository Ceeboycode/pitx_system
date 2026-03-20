<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class CompanyVehiclePolicy
{
    public function before(User $user, string $ability): bool
    {
        return $user->hasRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('external_vehicles.viewAny');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('external_vehicles.view')
            && $user->company_id === $vehicle->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('external_vehicles.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('external_vehicles.update')
            && $user->company_id === $vehicle->company_id;
    }

    public function toggleStatus(User $user, Vehicle $vehicle): bool
    {
        return $user->can('external_vehicles.toggleStatus')
            && $user->company_id === $vehicle->company_id;
    }

    public function downloadDocument(User $user, Vehicle $vehicle): bool
    {
        return $user->can('external_vehicle_documents.download')
            && $user->company_id === $vehicle->company_id;
    }

    public function uploadDocument(User $user, Vehicle $vehicle): bool
    {
        return $user->can('external_vehicle_documents.upload')
            && $user->company_id === $vehicle->company_id;
    }
}
