<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleType;

class VehicleTypePolicy
{
    /**
     * Handle all abilities before checking specific methods.
     */
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle_types.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_types.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('vehicle_types.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_types.update');
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function delete(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_types.archive');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_types.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_types.forceDelete');
    }

    /**
     * Determine whether the user can view the trash listing.
     */
    public function viewTrash(User $user): bool
    {
        return $user->can('vehicle_types.viewTrash');
    }
}
