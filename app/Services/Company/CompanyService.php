<?php
namespace App\Services\Company;

use App\Models\Company;

class CompanyService
{
    // Service methods for Company operations would go here
    public function createCompany(array $data, int $userId) : Company
    {
        $data['created_by'] = $userId;

        return Company::create($data);
    }

    public function updateCompany(Company $company, array $data, int $userId) : Company
    {
        $data['updated_by'] = $userId;

        $company->update($data);

        return $company;
    }

    public function deleteCompany(Company $company) : bool
    {
        return $company->delete();
    }

    public function restoreCompany(Company $company) : bool
    {
        return $company->restore();
    }

    public function forceDeleteCompany(Company $company) : bool
    {
        return $company->forceDelete();
    }
}



