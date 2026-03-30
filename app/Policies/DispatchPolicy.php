<?php

namespace App\Policies;

use App\Models\Dispatch;
use App\Models\User;

class DispatchPolicy
{
    /**
     * Determine if the user can view any dispatches
     */
    public function viewAny(User $user): bool
    {
        return $user->can('dispatches.viewAny');
    }

    /**
     * Determine if the user can view a specific dispatch
     */
    public function view(User $user, Dispatch $dispatch): bool
    {
        // Company users can view their own company's dispatches
        if ($user->company_id && $dispatch->company_id === $user->company_id) {
            return $user->can('dispatches.view');
        }

        // Internal users can view all dispatches
        if ($user->company_id === null) {
            return $user->can('dispatches.view');
        }

        return false;
    }

    /**
     * Determine if the user can create a dispatch
     */
    public function create(User $user): bool
    {
        // Only company users can create dispatches
        return $user->company_id !== null && $user->can('dispatches.create');
    }

    /**
     * Determine if the user can update a dispatch
     */
    public function update(User $user, Dispatch $dispatch): bool
    {
        // Only company users can update
        if ($user->company_id === null) {
            return false;
        }

        // User must belong to the dispatch's company
        if ($dispatch->company_id !== $user->company_id) {
            return false;
        }

        // Cannot edit departed dispatches
        if ($dispatch->status === Dispatch::STATUS_DEPARTED) {
            return false;
        }

        return $user->can('dispatches.update');
    }

    /**
     * Determine if the user can delete a dispatch
     */
    public function delete(User $user, Dispatch $dispatch): bool
    {
        // Only company users can delete
        if ($user->company_id === null) {
            return false;
        }

        // User must belong to the dispatch's company
        if ($dispatch->company_id !== $user->company_id) {
            return false;
        }

        // Cannot delete departed dispatches
        if ($dispatch->status === Dispatch::STATUS_DEPARTED) {
            return false;
        }

        return $user->can('dispatches.delete');
    }

    /**
     * Determine if the user can depart a dispatch
     */
    public function depart(User $user, Dispatch $dispatch): bool
    {
        // Only company users can depart their own dispatches
        if ($user->company_id === null) {
            return false;
        }

        return $dispatch->company_id === $user->company_id && $user->can('dispatches.depart');
    }
}
