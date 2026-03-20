<?php

namespace App\Policies;

use App\Models\Gate;
use App\Models\User;

class GatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gates.viewAny');
    }

    public function view(User $user, Gate $gate): bool
    {
        return $user->can('gates.view');
    }

    public function create(User $user): bool
    {
        return $user->can('gates.create');
    }

    public function update(User $user, Gate $gate): bool
    {
        return $user->can('gates.update');
    }

    public function delete(User $user, Gate $gate): bool
    {
        return $user->can('gates.delete');
    }

    public function restore(User $user, Gate $gate): bool
    {
        return $user->can('gates.restore');
    }

    public function forceDelete(User $user, Gate $gate): bool
    {
        return $user->can('gates.forceDelete');
    }

    public function viewTrash(User $user): bool
    {
        return $user->can('gates.viewTrash');
    }
}
