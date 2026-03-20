<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('companies.viewAny');
    }

    public function view(User $user, Company $company): bool
    {
        if (! $user->can('companies.view')) {
            return false;
        }

        if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
            return (int) $user->company_id === (int) $company->id;
        }

        return true;
    }

    // public function create(User $user): bool
    // {
    //     return $user->can('companies.create');
    // }

    // public function update(User $user, Company $company): bool
    // {
    //     if (! $user->can('companies.update')) {
    //         return false;
    //     }

    //     if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
    //         return (int) $user->company_id === (int) $company->id;
    //     }

    //     return true;
    // }

    public function delete(User $user, Company $company): bool
    {
        if (! $user->can('companies.delete')) {
            return false;
        }

        if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
            return (int) $user->company_id === (int) $company->id;
        }

        return true;
    }

    public function restore(User $user, Company $company): bool
    {
        if (! $user->can('companies.restore')) {
            return false;
        }

        if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
            return (int) $user->company_id === (int) $company->id;
        }

        return true;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->can('companies.forceDelete');
    }
}
