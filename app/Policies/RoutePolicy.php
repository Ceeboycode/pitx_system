<?php

namespace App\Policies;

use App\Models\Route;
use App\Models\User;

class RoutePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('routes.viewAny');
    }

    public function view(User $user, Route $route): bool
    {
        return $user->can('routes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('routes.create');
    }

    public function update(User $user, Route $route): bool
    {
        return $user->can('routes.update');
    }

    public function delete(User $user, Route $route): bool
    {
        return $user->can('routes.delete');
    }

    public function restore(User $user, Route $route): bool
    {
        return $user->can('routes.restore');
    }

    public function forceDelete(User $user, Route $route): bool
    {
        return $user->can('routes.forceDelete');
    }

    public function viewTrash(User $user): bool
    {
        return $user->can('routes.viewTrash');
    }

    public function toggleStatus(User $user, Route $route): bool
    {
        return $user->can('routes.toggleStatus');
    }
}
