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
        // companies.viewAny
        Gate::authorize('viewAny', Company::class);

        $status = $request->input('status', 'verified');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $allowedStatuses = ['all', 'draft', 'docs_completed', 'for_verification', 'verified', 'needs_revision', 'rejected'];
        if (filled($status) && ! in_array($status, $allowedStatuses, true)) {
            $status = 'verified';
        }

        $allowedSorts = ['company_name', 'company_code', 'status', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $companies = Company::query()
            ->select(
                'id',
                'company_name',
                'company_code',
                'company_email',
                'company_email_verified_at',
                'company_phone',
                'business_type',
                'status',
                'created_at'
            )
            ->search($request->search)
            ->when($status && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Index', [
            'companies' => $companies,
            'filters'   => [
                'search' => $request->search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function show(Company $company)
    {
        // companies.view
        Gate::authorize('view', $company);

        return $this->renderShow($company);
    }

    public function archivedShow(Company $company)
    {
        // companies.view
        Gate::authorize('view', $company);
        abort_unless($company->trashed(), 404);

        return $this->renderShow($company, true);
    }

    private function renderShow(Company $company, bool $archivedView = false)
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
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo)
            : null;

        return Inertia::render('Company/Show', [
            'company' => $company,
            'archivedView' => $archivedView,
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
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Trash', [
            'companies' => $companies,
            'filters'   => ['search' => $request->search],
        ]);
    }

    public function create()
    {
        return Inertia::render('Company/Create');
    }

    public function store(CompanyStoreRequest $request)
    {
        $userId = $request->user()?->id;
        abort_if(! $userId, 403);

        $company = $this->companyService->createCompanyWithDocuments(
            $request->validated(),
            $request,
            $userId
        );

        return redirect()
            ->route('companies.show', ['company' => $company->id])
            ->with('success', 'Company and documents submitted successfully.');
    }

    public function update(CompanyUpdateRequest $request, Company $company)
    {
        $company->update([
            'company_name' => $request->string('company_name')->toString(),
            'status' => $request->string('status')->toString(),
        ]);

        return back()->with('success', 'Company updated successfully.');
    }

    public function destroy(Request $request, Company $company)
    {
        // companies.delete
        Gate::authorize('delete', $company);

        $userId = $request->user()?->id;
        abort_if(! $userId, 403);

        $this->companyService->deleteCompany($company, $userId);

        return to_route('companies.index')->with('success', 'Company archived successfully.');
    }

    public function restore(Request $request, Company $company)
    {
        // companies.restore
        Gate::authorize('restore', $company);

        $this->companyService->restoreCompany($company);

        $referer = (string) $request->headers->get('referer', '');

        if (str_contains($referer, '/companies/archived/')) {
            return to_route('companies.show', ['company' => $company->id])
                ->with('success', 'Company restored successfully.');
        }

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
