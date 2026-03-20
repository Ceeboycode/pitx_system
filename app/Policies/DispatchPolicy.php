<?php

namespace App\Policies;

use App\Models\Dispatch;
use App\Models\User;

class DispatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dispatches.viewAny');
    }

    public function view(User $user): bool  // no Dispatch $dispatch param
    {
        return $user->can('dispatches.view');
    }
}
