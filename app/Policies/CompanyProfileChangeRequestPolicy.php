<?php

namespace App\Policies;

use App\Models\CompanyProfileChangeRequest;
use App\Models\User;

class CompanyProfileChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CompanyProfileChangeRequest $changeRequest): bool
    {
        if ($user->company_id === null) {
            return true;
        }

        return $user->company_id === $changeRequest->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function approve(User $user, CompanyProfileChangeRequest $changeRequest): bool
    {
        return $user->company_id === null && $changeRequest->isPending();
    }

    public function reject(User $user, CompanyProfileChangeRequest $changeRequest): bool
    {
        return $user->company_id === null && $changeRequest->isPending();
    }
}
