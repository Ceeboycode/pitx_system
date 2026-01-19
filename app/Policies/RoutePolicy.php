<?php

namespace App\Policies;

use App\Models\Route;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RoutePolicy
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
        return $user->can('route.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Route $route): bool
    {
        return $user->can('route.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('route.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Route $route): bool
    {
        return $user->can('route.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Route $route): bool
    {
        return $user->can('route.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Route $route): bool
    {
        return $user->can('route.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Route $route): bool
    {
        return $user->can('route.forceDelete');
    }
}
