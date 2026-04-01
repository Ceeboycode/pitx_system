<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanyProfileChangeRequest;
use App\Models\Company;
use App\Models\CompanyProfileChangeRequest;
use App\Notifications\Internal\CompanyProfileChangeSubmittedNotification;
use App\Services\Company\CompanyStatusService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly CompanyStatusService $companyStatusService,
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Show profile page
    |--------------------------------------------------------------------------
    */
    public function show(Request $request): Response
    {
        $user    = $request->user();
        $company = $user->company;

        $this->companyStatusService->markExpiredDocumentsAndSync(collect([$company]));
        $company = $company->fresh();

        $latestChangeRequest = CompanyProfileChangeRequest::query()
            ->where('company_id', $company->id)
            ->latest()
            ->first();

        $profileChangeRequests = CompanyProfileChangeRequest::query()
            ->where('company_id', $company->id)
            ->latest()
            ->get()
            ->map(fn (CompanyProfileChangeRequest $changeRequest): array => [
                'id' => $changeRequest->id,
                'status' => $changeRequest->status,
                'requested_values' => $changeRequest->requested_values,
                'rejection_reason' => $changeRequest->rejection_reason,
                'created_at' => $changeRequest->created_at?->toIso8601String(),
                'approved_at' => $changeRequest->approved_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('External/Settings/CompanyProfile', [
            'company' => [
                'id'                             => $company->id,
                'company_name'                   => $company->company_name,
                'company_code'                   => $company->company_code,
                'company_email'                  => $company->company_email,
                'company_phone'                  => $company->company_phone,
                'company_address'                => $company->company_address,
                'status'                         => $company->status,
                'business_type'                  => $company->business_type,
                'registration_number'            => $company->registration_number,
                'authorized_representative_name'     => $company->authorized_representative_name,
                'authorized_representative_position' => $company->authorized_representative_position,
                'authorized_representative_contact'  => $company->authorized_representative_contact,
                'logo_url' => filled($company->logo)
                    ? Storage::url($company->logo)
                    : null,
                'documents' => $company->documents()
                    ->select(['id', 'doc_type', 'status', 'remarks', 'expires_at', 'updated_at'])
                    ->get(),
            ],
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'latest_change_request' => $latestChangeRequest ? [
                'id' => $latestChangeRequest->id,
                'status' => $latestChangeRequest->status,
                'requested_values' => $latestChangeRequest->requested_values,
                'rejection_reason' => $latestChangeRequest->rejection_reason,
                'created_at' => $latestChangeRequest->created_at?->toIso8601String(),
                'approved_at' => $latestChangeRequest->approved_at?->toIso8601String(),
            ] : null,
            'profile_change_requests' => $profileChangeRequests,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update logo
    |--------------------------------------------------------------------------
    */
    public function submitUpdate(StoreCompanyProfileChangeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;
        $validated = $request->validated();

        $candidate = [
            'company_name' => $validated['company_name'] ?? $company->company_name,
            'company_email' => $validated['company_email'] ?? $company->company_email,
            'company_phone' => $validated['company_phone'] ?? $company->company_phone,
            'company_address' => $validated['company_address'] ?? $company->company_address,
            'business_type' => $validated['business_type'] ?? $company->business_type,
            'registration_number' => $validated['registration_number'] ?? $company->registration_number,
            'authorized_representative_name' => $validated['authorized_representative_name'] ?? $company->authorized_representative_name,
            'authorized_representative_position' => $validated['authorized_representative_position'] ?? $company->authorized_representative_position,
            'authorized_representative_contact' => $validated['authorized_representative_contact'] ?? $company->authorized_representative_contact,
        ];

        $requestedValues = [];
        $currentValues = [];

        foreach ($candidate as $field => $value) {
            if ($company->{$field} !== $value) {
                $requestedValues[$field] = $value;
                $currentValues[$field] = $company->{$field};
            }
        }

        $isBusinessTypeChanged = array_key_exists('business_type', $requestedValues);
        $isRegistrationNumberChanged = array_key_exists('registration_number', $requestedValues);
        $hasMajorChange = $isBusinessTypeChanged || $isRegistrationNumberChanged;

        if ($request->boolean('remove_logo') === true && $company->logo !== null) {
            $requestedValues['logo'] = null;
            $currentValues['logo'] = $company->logo;
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $logoPublicPath = 'company-logos/pending/' . Str::uuid() . '.' . $ext;

            Storage::disk('public')->put(
                $logoPublicPath,
                file_get_contents($file->getRealPath())
            );

            $requestedValues['logo'] = $logoPublicPath;
            $currentValues['logo'] = $company->logo;
        }

        if ($hasMajorChange) {
            $requiredCertificationType = $this->requiredCertificationType($candidate['business_type'] ?? $company->business_type);

            $request->validate([
                'compliance_document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'compliance_document_issued_at' => ['required', 'date', 'before_or_equal:today'],
                'compliance_document_expires_at' => ['required', 'date', 'after:compliance_document_issued_at'],
            ], [
                'compliance_document_file.required' => "{$requiredCertificationType} is required when changing business type or registration number.",
                'compliance_document_issued_at.required' => 'Issue date is required for the compliance certificate.',
                'compliance_document_expires_at.required' => 'Expiry date is required for the compliance certificate.',
            ]);

            $documentFile = $request->file('compliance_document_file');
            $documentExt = strtolower($documentFile->getClientOriginalExtension() ?: 'pdf');
            $documentPath = $documentFile->storeAs(
                "company-documents/pending/{$company->id}/{$requiredCertificationType}",
                Str::uuid() . '.' . $documentExt,
                'public'
            );

            $requestedValues['_supporting_documents'] = [
                $requiredCertificationType => [
                    'doc_type' => $requiredCertificationType,
                    'file_path' => $documentPath,
                    'original_name' => $documentFile->getClientOriginalName(),
                    'mime_type' => $documentFile->getMimeType(),
                    'file_size' => $documentFile->getSize(),
                    'issued_at' => (string) $request->input('compliance_document_issued_at'),
                    'expires_at' => (string) $request->input('compliance_document_expires_at'),
                ],
            ];
        }

        if (empty($requestedValues)) {
            return back()->withErrors(['profile' => 'No profile changes detected.']);
        }

        $changeRequest = CompanyProfileChangeRequest::query()->create([
            'company_id' => $company->id,
            'requested_by' => $user->id,
            'status' => CompanyProfileChangeRequest::STATUS_PENDING,
            'requested_values' => $requestedValues,
            'current_values' => $currentValues,
        ]);

        if ($hasMajorChange && $company->status === Company::STATUS_VERIFIED) {
            $company->update(['status' => Company::STATUS_FOR_VERIFICATION]);
        }

        $changeRequest->load(['company', 'requester']);
        $this->notificationService->notifyInternalUsers(
            new CompanyProfileChangeSubmittedNotification($changeRequest),
            ['super-admin', 'admin', 'terminal manager']
        );

        return redirect()->back()->with('success', 'Profile update submitted for admin verification.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        if ($company->logo === null) {
            return back()->withErrors(['logo' => 'No active logo to remove.']);
        }

        $pendingExists = CompanyProfileChangeRequest::query()
            ->where('company_id', $company->id)
            ->where('status', CompanyProfileChangeRequest::STATUS_PENDING)
            ->exists();

        if ($pendingExists) {
            return back()->withErrors(['logo' => 'You already have a pending profile update request under review.']);
        }

        $changeRequest = CompanyProfileChangeRequest::query()->create([
            'company_id' => $company->id,
            'requested_by' => $user->id,
            'status' => CompanyProfileChangeRequest::STATUS_PENDING,
            'requested_values' => ['logo' => null],
            'current_values' => ['logo' => $company->logo],
        ]);

        $changeRequest->load(['company', 'requester']);
        $this->notificationService->notifyInternalUsers(
            new CompanyProfileChangeSubmittedNotification($changeRequest),
            ['super-admin', 'admin', 'terminal manager']
        );

        return back()->with('success', 'Logo removal submitted for admin verification.');
    }

    private function requiredCertificationType(?string $businessType): string
    {
        return $businessType === 'corporate' ? 'SEC_CERT' : 'DTI_CERT';
    }
}
