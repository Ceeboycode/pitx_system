<?php

namespace App\Policies;

use App\Models\CompanyDocument;
use App\Models\User;

class CompanyDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company_documents.viewAny');
    }

    public function view(User $user, CompanyDocument $companyDocument): bool
    {
        if (! $user->can('company_documents.view')) {
            return false;
        }

        if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
            return (int) $user->company_id === (int) $companyDocument->company_id;
        }

        return true;
    }

    public function update(User $user, CompanyDocument $companyDocument): bool
    {
        if (! $user->can('company_documents.update')) {
            return false;
        }

        if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
            return (int) $user->company_id === (int) $companyDocument->company_id;
        }

        return true;
    }

    public function delete(User $user, CompanyDocument $companyDocument): bool
    {
        if (! $user->can('company_documents.delete')) {
            return false;
        }

        if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
            return (int) $user->company_id === (int) $companyDocument->company_id;
        }

        return true;
    }

    public function verify(User $user, CompanyDocument $companyDocument): bool
    {
        return $user->can('company_documents.verify');
    }

    public function reject(User $user, CompanyDocument $companyDocument): bool
    {
        return $user->can('company_documents.reject');
    }

    public function download(User $user, CompanyDocument $companyDocument): bool
    {
        if (! $user->can('company_documents.download')) {
            return false;
        }

        if ($user->hasAnyRole(['operator', 'dispatcher', 'driver'])) {
            return (int) $user->company_id === (int) $companyDocument->company_id;
        }

        return true;
    }
}
