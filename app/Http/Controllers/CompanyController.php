<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyStoreRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Company::class);

        $companies = Company::query()
            ->select('id', 'company_name', 'created_at')
            ->search($request->search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Index', [
            'companies' => $companies,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function show(Company $company)
    {
        Gate::authorize('view', $company);

        $company->load([
            'creator:id,name',
            'updater:id,name',
        ]);

        return Inertia::render('Company/Show', [
            'company' => $company,
        ]);
    }

    public function trash(Request $request)
    {
        Gate::authorize('viewAny', Company::class);

        $companies = Company::onlyTrashed()
            ->select('id', 'company_name', 'deleted_at', 'deleted_by')
            ->with(['deleter:id,name'])
            ->search($request->search)
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

    public function store(CompanyStoreRequest $request)
    {
        Gate::authorize('create', Company::class);

        $this->companyService->createCompany(
            $request->validated(),
            auth()->user()->id
        );

        return back()->with('success', 'Company created successfully.');
    }

    public function update(CompanyUpdateRequest $request, Company $company)
    {
        Gate::authorize('update', $company);

        $this->companyService->updateCompany(
            $company,
            $request->validated(),
            auth()->user()->id
        );

        return back()->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        Gate::authorize('delete', $company);

        $this->companyService->deleteCompany($company, auth()->user()->id);

        return back()->with('success', 'Company archived successfully.');
    }

    public function restore(Company $company)
    {
        Gate::authorize('restore', $company);

        $this->companyService->restoreCompany($company);

        return back()->with('success', 'Company restored successfully.');
    }

    public function forceDelete(Company $company)
    {
        Gate::authorize('forceDelete', $company);

        $this->companyService->forceDeleteCompany($company);

        return back()->with('success', 'Company permanently deleted successfully.');
    }
}
