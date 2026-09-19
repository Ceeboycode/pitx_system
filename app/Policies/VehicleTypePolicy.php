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
        return $user->can('vehicle_types.viewAny');
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
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_types.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VehicleType $vehicleType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VehicleType $vehicleType): bool
    {
        return false;
    }
}
