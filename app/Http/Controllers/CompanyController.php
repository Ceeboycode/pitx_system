<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class CompanyController extends Controller
{

    public function __construct(
        private CompanyService $companyService
    ) {}

    // Display a listing of the resource.
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Company::class);

        $companies = Company::query()
            ->select('id', 'company_name', 'created_at')
            ->when($request->search, function ($query, $search) {
                $query->where('company_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Index', [
            // dd($companies),
            'companies' => $companies,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function show(Company $company)
    {
        Gate::authorize('view', $company);

        return Inertia::render('Company/Show', [
            'company' => $company->load(['creator', 'updater']),
        ]);
    }

    public function trash(Request $request)
    {
        $companies = Company::onlyTrashed()
            ->select('id', 'company_name', 'deleted_at', 'deleted_by')
            ->with(['deleter:id,name'])
            ->when($request->search, function ($query, $search) {
                $query->where('company_name', 'like', "%{$search}%");
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Trash', [
            'companies' => $companies,
            'filters' => [
                'search' => $request->search,
            ],
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
        return to_route('companies.index')->with('success', 'Company created successfully.');
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
        return to_route('companies.index')->with('success', 'Company updated successfully.');
    }

    // Implementation for sofre deleting a company
    public function destroy(Company $company)
    {
        // 1. Authorize FIRST
        Gate::authorize('delete', $company);

        // 2. Delegate to Service
        $this->companyService->deleteCompany($company, auth()->id());

        // 3. Redirect back to the company index with a success message
        return to_route('companies.index')->with('success', 'Company archived successfully.');
    }

    // Restore a soft-deleted company.
    public function restore(Company $company)
    {
        Gate::authorize('restore', $company);

        $this->companyService->restoreCompany($company);

        return to_route('companies.trash')->with('success', 'Company restored successfully.');
    }

    public function forceDelete(Company $company)
    {
        Gate::authorize('forceDelete', $company);

        $this->companyService->forceDeleteCompany($company);

        return to_route('companies.trash')->with('success', 'Company permanently deleted successfully.');
    }

}
