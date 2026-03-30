<?php

namespace App\Policies;

use App\Models\DispatchChangeRequest;
use App\Models\User;

class DispatchChangeRequestPolicy
{
    /**
     * Determine if the user can view any change requests
     */
    public function viewAny(User $user): bool
    {
        if ($user->company_id === null) {
            // Internal users can see all requests
            return true;
        }

        // Company users can see their own company's requests
        return true;
    }

    /**
     * Determine if the user can view a specific change request
     */
    public function view(User $user, DispatchChangeRequest $changeRequest): bool
    {
        if ($user->company_id === null) {
            // Internal users can see all requests
            return true;
        }

        // Company users can only see requests for their company's dispatches
        return $changeRequest->dispatch->company_id === $user->company_id;
    }

    /**
     * Determine if the user can create a change request
     */
    public function create(User $user): bool
    {
        // Only company users (external) can create change requests
        // and only for their own company dispatches
        return $user->company_id !== null;
    }

    /**
     * Determine if the user can update a change request (generally not allowed)
     */
    public function update(User $user, DispatchChangeRequest $changeRequest): bool
    {
        // Only pending requests can be modified, and only by the requester
        return $changeRequest->isPending() && $changeRequest->requested_by === $user->id;
    }

    /**
     * Determine if the user can delete a change request
     */
    public function delete(User $user, DispatchChangeRequest $changeRequest): bool
    {
        // Only pending requests can be deleted
        return $changeRequest->isPending() && (
            $changeRequest->requested_by === $user->id ||
            $user->company_id === null // Internal users can delete any pending
        );
    }

    /**
     * Determine if the user can approve a change request
     */
    public function approve(User $user, DispatchChangeRequest $changeRequest): bool
    {
        // Only internal users (no company_id) can approve
        return $user->company_id === null && $changeRequest->isPending();
    }

    /**
     * Determine if the user can reject a change request
     */
    public function reject(User $user, DispatchChangeRequest $changeRequest): bool
    {
        // Only internal users (no company_id) can reject
        return $user->company_id === null && $changeRequest->isPending();
    }
}
