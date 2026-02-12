<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\Response;

class VehiclePolicy
{
    public function before(User $user, string $ability): bool
    {
        return $user->hasRole('admin');
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('vehicle.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('vehicle.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('vehicle.forceDelete');
    }
}
