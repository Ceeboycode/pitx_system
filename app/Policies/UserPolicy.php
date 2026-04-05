<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.viewAny');
    }

    public function viewTrash(User $user): bool
    {
        return $user->can('users.viewTrash');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update') && $user->id !== $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('users.archive') && $user->id !== $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('users.restore');
    }

    public function toggleStatus(User $user, User $model): bool
    {
        return $user->can('users.toggleStatus') && $user->id !== $model->id;
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $user->can('users.resetPassword') && $user->id !== $model->id;
    }
}
