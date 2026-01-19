<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{

    public function __construct(
        private CompanyService $companyService
    ) {}


    // Display a listing of the resource.
    public function index()
    {
        Gate::authorize('viewAny', Company::class);
        $companies = Company::select('id', 'company_name')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Index', [
            'companies' => $companies,
        ]);
    }

    // Display the specified resource.
    public function show(Company $company)
    {
        Gate::authorize('view', $company);

        return Inertia::render('Company/Show', [
            'company' => $company->load(['creator', 'updater']),
        ]);
    }

    // Implementation for storing a new company
    public function store(CompanyStoreRequest $request)
    {
        // 1. Authorize FIRST
        Gate::authorize('create', Company::class);

        // 2. Validated data
        $validated = $request->validated();

        // 3. Delegate to Service
        $this->companyService->createCompany($validated, auth()->id());

        // 4. Redirect back to the company index with a success message
        return redirect()->back();
    }

    // Implementation for updating an existing company
    public function update(CompanyUpdateRequest $request, Company $company)
    {
        // 1. Authorize FIRST
        Gate::authorize('update', $company);

        // 2. Validated data
        $validated = $request->validated();

        // 3. Delegate to Service
        $this->companyService->updateCompany($company, $validated, auth()->id());

        // 4. Redirect back to the company index with a success message
        return redirect()->back();
    }

    // Implementation for sofre deleting a company
    public function destroy(Company $company)
    {
        // 1. Authorize FIRST
        Gate::authorize('delete', $company);

        // 2. Delegate to Service
        $this->companyService->deleteCompany($company);

        // 3. Redirect back to the company index with a success message
        return redirect()->back();
    }

    // Display a listing of soft-deleted companies.
    public function trash()
    {
        $companies = Company::onlyTrashed()
            ->select('id', 'company_name', 'deleted_at')
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Trash', [
            'companies' => $companies,
        ]);
    }

    // Restore a soft-deleted company.
    public function restore(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);

        Gate::authorize('restore', $company);

        $this->companyService->restoreCompany($company);

        return redirect()->back();
    }

    // Permanently delete a soft-deleted company.
    public function forceDelete(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);

        Gate::authorize('forceDelete', $company);

        $this->companyService->forceDeleteCompany($company);

        return redirect()->back();
    }
}
