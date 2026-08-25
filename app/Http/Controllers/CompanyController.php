<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Company::class);

        $allowedSorts = ['company_name', 'company_code', 'status', 'is_active', 'created_at'];
        $sortBy = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'created_at';
        $sortDir = in_array($request->sort_dir, ['asc', 'desc']) ? $request->sort_dir : 'desc';

        $companies = Company::query()
            ->select(
                'id',
                'company_name',
                'company_code',
                'company_email',
                'company_email_verified_at',
                'company_phone',
                'business_type',
                'logo',
                'status',
                'is_active',
                'created_at'
            )
            ->search($request->search)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy($sortBy, $sortDir)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Index', [
            'companies' => $companies,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'sort_by' => $request->sort_by,
                'sort_dir' => $request->sort_dir,
            ],
        ]);
    }

    public function show(Company $company)
    {
        // companies.view
        Gate::authorize('view', $company);

        $company->load([
            'creator:id,name',
            'updater:id,name',
            'documents' => function ($q) {
                $q->select(
                    'id',
                    'company_id',
                    'doc_type',
                    'status',
                    'remarks',
                    'original_name',
                    'file_path',
                    'mime_type',
                    'issued_at',
                    'expires_at',
                    'uploaded_by',
                    'verified_by',
                    'verified_at',
                    'created_at'
                )->latest();
            },
            'documents.uploader:id,name',
            'documents.verifier:id,name',
        ]);

        $company->logo_url = filled($company->logo)
            ? \Illuminate\Support\Facades\Storage::url($company->logo)
            : null;

        return Inertia::render('Company/Show', [
            'company' => $company,
        ]);
    }

    public function trash(Request $request)
    {
        // companies.viewAny
        Gate::authorize('viewAny', Company::class);

        $companies = Company::onlyTrashed()
            ->select(
                'id',
                'company_name',
                'company_code',
                'company_email',
                'company_phone',
                'business_type',
                'deleted_at',
                'deleted_by'
            )
            ->with(['deleter:id,name'])
            ->search($request->search)
            ->when($request->filled('business_type'), fn ($q) => $q->where('business_type', 'like', "%{$request->business_type}%"))
            ->when($request->filled('archived_by'), fn ($q) => $q->whereHas(
                'deleter',
                fn ($deleter) => $deleter->where('name', 'like', "%{$request->archived_by}%")
            ))
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Trash', [
            'companies' => $companies,
            'filters' => $request->only('search', 'business_type', 'archived_by'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Company/Create');
    }

    public function edit(Company $company): Response
    {
        Gate::authorize('update', $company);

        return Inertia::render('Company/Edit', [
            'company' => $company->only([
                'id',
                'company_name',
                'status',
                'is_active',
            ]),
        ]);
    }

    public function update(CompanyUpdateRequest $request, Company $company): RedirectResponse
    {
        $userId = $request->user()?->id;
        abort_if(! $userId, 403);

        $this->companyService->updateActiveStatus(
            $company,
            $request->boolean('is_active'),
            $userId
        );

        return back()->with('success', 'Company active status updated successfully.');
    }

    public function destroy(Request $request, Company $company)
    {
        // companies.delete
        Gate::authorize('delete', $company);

        $userId = $request->user()?->id;
        abort_if(! $userId, 403);

        $this->companyService->deleteCompany($company, $userId);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company archived successfully.');
    }

    public function restore(Company $company)
    {
        // companies.restore
        Gate::authorize('restore', $company);

        $this->companyService->restoreCompany($company);

        return back()->with('success', 'Company restored successfully.');
    }

    public function forceDelete(Company $company)
    {
        // companies.forceDelete
        Gate::authorize('forceDelete', $company);

        $this->companyService->forceDeleteCompany($company);

        return back()->with('success', 'Company permanently deleted.');
    }
}
