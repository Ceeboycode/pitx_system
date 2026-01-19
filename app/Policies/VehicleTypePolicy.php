<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Auth\Access\Response;

class VehicleTypePolicy
{
    /**
     * Handle all abilities before checking specific methods.
     */
    public function before(User $user, string $ability): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle_type.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_type.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('vehicle_type.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_type.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_type.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_type.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VehicleType $vehicleType): bool
    {
        return $user->can('vehicle_type.forceDelete');
    }
}
