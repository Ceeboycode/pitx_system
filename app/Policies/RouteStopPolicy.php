<?php

namespace App\Policies;

use App\Models\RouteStop;
use App\Models\User;

class RouteStopPolicy
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
        return $user->can('route_stop.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RouteStop $routeStop): bool
    {
        return $user->can('route_stop.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('route_stop.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RouteStop $routeStop): bool
    {
        return $user->can('route_stop.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RouteStop $routeStop): bool
    {
        return $user->can('route_stop.archive');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RouteStop $routeStop): bool
    {
        return $user->can('route_stop.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RouteStop $routeStop): bool
    {
        return $user->can('route_stop.forceDelete');
    }
}
