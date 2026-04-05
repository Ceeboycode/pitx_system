<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vehicles.viewAny');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('vehicles.create');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.update');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.archive');
    }

    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.restore');
    }

    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.forceDelete');
    }

    public function verifyDocument(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle_documents.verify');
    }

    public function invalidateDocument(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle_documents.invalidate');
    }

    public function unverifyDocument(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle_documents.unverify');
    }

    public function toggleStatus(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicles.toggleStatus');
    }
}
