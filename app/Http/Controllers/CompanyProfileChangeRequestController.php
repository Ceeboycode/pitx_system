<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\ApproveCompanyProfileChangeRequest;
use App\Http\Requests\Company\RejectCompanyProfileChangeRequest;
use App\Models\CompanyProfileChangeRequest;
use App\Notifications\External\CompanyProfileChangeApprovedNotification;
use App\Notifications\External\CompanyProfileChangeRejectedNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileChangeRequestController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CompanyProfileChangeRequest::class);

        $requests = CompanyProfileChangeRequest::query()
            ->with(['company:id,company_name,company_code,status', 'requester:id,name,email', 'approver:id,name'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function (CompanyProfileChangeRequest $changeRequest): array {
                $requestedValues = $changeRequest->requested_values ?? [];
                $currentValues = $changeRequest->current_values ?? [];
                $supportingDocuments = array_values($requestedValues['_supporting_documents'] ?? []);
                $hasLogoChange = array_key_exists('logo', $requestedValues);
                $newLogoPath = $hasLogoChange ? ($requestedValues['logo'] ?? null) : null;
                $oldLogoPath = $hasLogoChange ? ($currentValues['logo'] ?? null) : null;

                unset($requestedValues['_supporting_documents']);
                unset($requestedValues['logo']);

                return [
                    'id' => $changeRequest->id,
                    'company_id' => $changeRequest->company_id,
                    'status' => $changeRequest->status,
                    'requested_values' => $requestedValues,
                    'current_values' => $currentValues,
                    'logo_change' => [
                        'has_change' => $hasLogoChange,
                        'new_preview_url' => filled($newLogoPath) ? Storage::url((string) $newLogoPath) : null,
                        'old_preview_url' => filled($oldLogoPath) ? Storage::url((string) $oldLogoPath) : null,
                        'is_remove' => $hasLogoChange && blank($newLogoPath),
                    ],
                    'supporting_documents' => array_map(function (array $doc): array {
                        return [
                            'doc_type' => $doc['doc_type'] ?? null,
                            'original_name' => $doc['original_name'] ?? null,
                            'mime_type' => $doc['mime_type'] ?? null,
                            'issued_at' => $doc['issued_at'] ?? null,
                            'expires_at' => $doc['expires_at'] ?? null,
                            'file_path' => $doc['file_path'] ?? null,
                            'preview_url' => filled($doc['file_path'] ?? null) ? Storage::url((string) $doc['file_path']) : null,
                        ];
                    }, $supportingDocuments),
                    'rejection_reason' => $changeRequest->rejection_reason,
                    'approved_at' => $changeRequest->approved_at?->toIso8601String(),
                    'created_at' => $changeRequest->created_at?->toIso8601String(),
                    'company' => $changeRequest->company ? [
                        'id' => $changeRequest->company->id,
                        'company_name' => $changeRequest->company->company_name,
                        'company_code' => $changeRequest->company->company_code,
                        'status' => $changeRequest->company->status,
                        'show_url' => route('companies.show', $changeRequest->company),
                    ] : null,
                    'requester' => $changeRequest->requester ? [
                        'id' => $changeRequest->requester->id,
                        'name' => $changeRequest->requester->name,
                        'email' => $changeRequest->requester->email,
                    ] : null,
                    'approver' => $changeRequest->approver ? [
                        'id' => $changeRequest->approver->id,
                        'name' => $changeRequest->approver->name,
                    ] : null,
                ];
            });

        return Inertia::render('CompanyProfileChangeRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function approve(
        CompanyProfileChangeRequest $changeRequest,
        ApproveCompanyProfileChangeRequest $request,
    ): RedirectResponse {
        Gate::authorize('approve', $changeRequest);

        $changeRequest->loadMissing(['company', 'requester']);
        $changeRequest->approve($request->user());
        $changeRequest->refresh();

        $approvedNotification = new CompanyProfileChangeApprovedNotification($changeRequest);
        $this->notificationService->notifyCompanyUsers($changeRequest->company, $approvedNotification);
        $this->notificationService->notifyCompanyEmail($changeRequest->company, $approvedNotification);

        return redirect()
            ->route('companies.show', $changeRequest->company)
            ->with('success', 'Profile change request approved.');
    }

    public function reject(
        CompanyProfileChangeRequest $changeRequest,
        RejectCompanyProfileChangeRequest $request,
    ): RedirectResponse {
        Gate::authorize('reject', $changeRequest);

        $changeRequest->loadMissing(['company', 'requester']);
        $changeRequest->reject($request->user(), (string) $request->string('rejection_reason'));
        $changeRequest->refresh();

        $rejectedNotification = new CompanyProfileChangeRejectedNotification($changeRequest);
        $this->notificationService->notifyCompanyUsers($changeRequest->company, $rejectedNotification);
        $this->notificationService->notifyCompanyEmail($changeRequest->company, $rejectedNotification);

        return back()->with('success', 'Profile change request rejected.');
    }
}
