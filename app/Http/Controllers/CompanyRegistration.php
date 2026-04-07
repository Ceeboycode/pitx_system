<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationOtpMail;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use App\Notifications\External\CompanyResubmittedReceivedNotification;
use App\Notifications\External\CompanySubmissionReceivedNotification;
use App\Notifications\Internal\CompanyResubmittedNotification;
use App\Notifications\Internal\NewCompanySubmittedNotification;
use App\Services\Company\CompanyStatusService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanyRegistration extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly CompanyStatusService $companyStatusService,
    ) {
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $company = $user->company;

            if ($company) {
                return redirect()->route('registration.status');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return Inertia::render('CompanyRegistration');
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => $this->normalizePhMobile($request->input('phone')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', "regex:/^[A-Za-z][A-Za-z\s.'-]*$/"],
            'email' => ['required', 'string', 'max:255', 'email:rfc,dns', 'unique:users,email'],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+639\d{9}$/',
                'unique:users,phone_number',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'name.regex' => 'Name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'email.email' => 'Please enter a valid email address (e.g., name@example.com).',
            'email.unique' => 'This email address is already in use. Try signing in or use a different email.',
            'phone.regex' => 'Phone number must be a valid PH mobile number (09XXXXXXXXX or +639XXXXXXXXX).',
            'phone.unique' => 'This phone number is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $otp = $this->generateOtp();

        $request->session()->put('registration.step1', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
        ]);

        $request->session()->put('registration.otp.account', [
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified' => false,
        ]);

        Mail::to($validated['email'])->send(
            new RegistrationOtpMail($otp, 'account', $validated['name'])
        );

        return redirect()->back(303);
    }

    public function verifyStep1Otp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ], [
            'otp.digits' => 'The code must be exactly 6 digits.',
        ]);

        $stored = $request->session()->get('registration.otp.account');

        if (! $stored) {
            return redirect()->back(303)->withErrors([
                'otp' => 'No code was sent. Please go back and retry.',
            ]);
        }

        if (now()->isAfter($stored['expires_at'])) {
            $request->session()->forget('registration.otp.account');

            return redirect()->back(303)->withErrors([
                'otp' => 'Code expired. Please request a new one.',
            ]);
        }

        if (! Hash::check($request->input('otp'), $stored['code'])) {
            return redirect()->back(303)->withErrors([
                'otp' => 'Invalid code. Please try again.',
            ]);
        }

        $request->session()->put('registration.otp.account.verified', true);

        return redirect()->back(303);
    }

    public function resendStep1Otp(Request $request): RedirectResponse
    {
        $step1 = $request->session()->get('registration.step1');

        if (! $step1) {
            return redirect()->back(303)->withErrors([
                'otp' => 'Session expired. Please start over.',
            ]);
        }

        $otp = $this->generateOtp();

        $request->session()->put('registration.otp.account', [
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified' => false,
        ]);

        Mail::to($step1['email'])->send(
            new RegistrationOtpMail($otp, 'account', $step1['name'])
        );

        return redirect()->back(303)->with('resent', 'A new code has been sent to your email.');
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        $request->merge([
            'registration_number' => Str::upper(preg_replace('/\s+/', '', (string) $request->input('registration_number'))),
        ]);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255', "regex:/^[A-Za-z][A-Za-z\s.'-]*$/"],
            'company_email' => ['required', 'string', 'max:255', 'email:rfc,dns', 'unique:companies,company_email'],
            'company_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-()\s]{7,20}$/', 'unique:companies,company_phone'],
            'company_address' => ['required', 'string', 'max:500'],
            'business_type' => ['required', 'in:corporate,sole_proprietorship'],
            'registration_number' => [
                'required',
                'string',
                'max:100',
                'unique:companies,registration_number',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $registrationNumber = trim((string) $value);
                    $businessType = $request->input('business_type');

                    if ($businessType === 'sole_proprietorship') {
                        if (! preg_match('/^\d{7}$/', $registrationNumber)) {
                            $fail('For sole proprietorship, enter the 7-digit DTI Registration Number.');
                        }

                        return;
                    }

                    if ($businessType === 'corporate') {
                        if (! preg_match('/^[A-Z0-9-]+$/', $registrationNumber)) {
                            $fail('For corporate entities, SEC Registration Number may contain letters, numbers, and hyphens only.');

                            return;
                        }

                        $coreLength = strlen(str_replace('-', '', $registrationNumber));

                        if ($coreLength < 9 || $coreLength > 12) {
                            $fail('For corporate entities, SEC Registration Number is usually 9 to 12 characters (excluding hyphens).');
                        }
                    }
                },
            ],
            'authorized_representative_name' => ['required', 'string', 'max:255', "regex:/^[A-Za-z][A-Za-z\s.'-]*$/"],
            'authorized_representative_position' => ['required', 'string', 'max:255'],
            'authorized_representative_contact' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-()\s]{7,20}$/', 'unique:companies,authorized_representative_contact'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'company_name.regex' => 'Company name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'company_email.email' => 'Please enter a valid company email address (e.g., company@example.com).',
            'company_email.unique' => 'This company email is already registered.',
            'company_phone.regex' => 'Company phone must be a valid number (digits, spaces, +, -, and parentheses only).',
            'company_phone.unique' => 'This company phone number is already registered.',
            'authorized_representative_name.regex' => 'Authorized representative name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'authorized_representative_contact.regex' => 'Authorized representative contact must be a valid number (digits, spaces, +, -, and parentheses only).',
            'authorized_representative_contact.unique' => 'This authorized representative contact is already registered.',
            'business_type.in' => 'Business type must be Corporate or Sole Proprietorship.',
            'logo.image' => 'The logo must be an image file.',
            'logo.mimes' => 'Logo must be a JPG, PNG, or WebP file.',
            'logo.max' => 'Logo must not exceed 2 MB.',
        ]);

        $otp = $this->generateOtp();
        $step1 = $request->session()->get('registration.step1');

        $logoTempPath = null;

        if ($request->hasFile('logo')) {
            $logoTempPath = $request->file('logo')->store('registration-logos/temp', 'local');
        }

        $previousTemp = $request->session()->get('registration.step2.logo_temp_path');
        if ($previousTemp && Storage::disk('local')->exists($previousTemp)) {
            Storage::disk('local')->delete($previousTemp);
        }

        unset($validated['logo']);

        $request->session()->put('registration.step2', array_merge(
            $validated,
            ['logo_temp_path' => $logoTempPath]
        ));

        $request->session()->put('registration.otp.company', [
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified' => false,
        ]);

        Mail::to($validated['company_email'])->send(
            new RegistrationOtpMail($otp, 'company', $step1['name'] ?? $validated['company_name'])
        );

        return redirect()->back(303);
    }

    public function verifyStep2Otp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ], [
            'otp.digits' => 'The code must be exactly 6 digits.',
        ]);

        $stored = $request->session()->get('registration.otp.company');

        if (! $stored) {
            return redirect()->back(303)->withErrors([
                'otp' => 'No code was sent. Please go back and retry.',
            ]);
        }

        if (now()->isAfter($stored['expires_at'])) {
            $request->session()->forget('registration.otp.company');

            return redirect()->back(303)->withErrors([
                'otp' => 'Code expired. Please request a new one.',
            ]);
        }

        if (! Hash::check($request->input('otp'), $stored['code'])) {
            return redirect()->back(303)->withErrors([
                'otp' => 'Invalid code. Please try again.',
            ]);
        }

        $request->session()->put('registration.otp.company.verified', true);

        return redirect()->back(303);
    }

    public function resendStep2Otp(Request $request): RedirectResponse
    {
        $step1 = $request->session()->get('registration.step1');
        $step2 = $request->session()->get('registration.step2');

        if (! $step2) {
            return redirect()->back(303)->withErrors([
                'otp' => 'Session expired. Please go back to step 2.',
            ]);
        }

        $otp = $this->generateOtp();

        $request->session()->put('registration.otp.company', [
            'code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified' => false,
        ]);

        Mail::to($step2['company_email'])->send(
            new RegistrationOtpMail($otp, 'company', $step1['name'] ?? $step2['company_name'])
        );

        return redirect()->back(303)->with('resent', 'A new code has been sent to your company email.');
    }

    public function storeStep3(Request $request): RedirectResponse
    {
        $step1 = $request->session()->get('registration.step1');
        $step2 = $request->session()->get('registration.step2');

        if (! $step1 || ! $step2) {
            return redirect()
                ->route('company-registration.show')
                ->withErrors(['session' => 'Session expired. Please start from step 1.']);
        }

        if (! $request->session()->get('registration.otp.account.verified')) {
            return redirect()->back(303)->withErrors([
                'session' => 'Please verify your account email before submitting.',
            ]);
        }

        if (! $request->session()->get('registration.otp.company.verified')) {
            return redirect()->back(303)->withErrors([
                'session' => 'Please verify your company email before submitting.',
            ]);
        }

        $isCorporate = $step2['business_type'] === 'corporate';

        $request->validate([
            'documents.MAYORS_PERMIT.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.MAYORS_PERMIT.issued_at' => ['required', 'date', 'before_or_equal:today'],
            'documents.MAYORS_PERMIT.expires_at' => ['required', 'date', 'after:documents.MAYORS_PERMIT.issued_at', 'after_or_equal:today'],

            'documents.BIR_2303.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.BIR_2303.issued_at' => ['required', 'date', 'before_or_equal:today'],
            'documents.BIR_2303.expires_at' => ['required', 'date', 'after:documents.BIR_2303.issued_at', 'after_or_equal:today'],

            'documents.AUTHORIZATION_LETTER.file' => ['nullable', 'required_with:documents.AUTHORIZATION_LETTER.issued_at,documents.AUTHORIZATION_LETTER.expires_at', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.AUTHORIZATION_LETTER.issued_at' => ['nullable', 'required_with:documents.AUTHORIZATION_LETTER.file', 'date', 'before_or_equal:today'],
            'documents.AUTHORIZATION_LETTER.expires_at' => ['nullable', 'required_with:documents.AUTHORIZATION_LETTER.file', 'date', 'after:documents.AUTHORIZATION_LETTER.issued_at', 'after_or_equal:today'],

            'documents.SEC_CERT.file' => [$isCorporate ? 'required' : 'nullable', 'required_with:documents.SEC_CERT.issued_at,documents.SEC_CERT.expires_at', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.SEC_CERT.issued_at' => [$isCorporate ? 'required' : 'nullable', 'required_with:documents.SEC_CERT.file', 'date', 'before_or_equal:today'],
            'documents.SEC_CERT.expires_at' => [$isCorporate ? 'required' : 'nullable', 'required_with:documents.SEC_CERT.file', 'date', 'after:documents.SEC_CERT.issued_at', 'after_or_equal:today'],

            'documents.DTI_CERT.file' => [! $isCorporate ? 'required' : 'nullable', 'required_with:documents.DTI_CERT.issued_at,documents.DTI_CERT.expires_at', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.DTI_CERT.issued_at' => [! $isCorporate ? 'required' : 'nullable', 'required_with:documents.DTI_CERT.file', 'date', 'before_or_equal:today'],
            'documents.DTI_CERT.expires_at' => [! $isCorporate ? 'required' : 'nullable', 'required_with:documents.DTI_CERT.file', 'date', 'after:documents.DTI_CERT.issued_at', 'after_or_equal:today'],
        ], $this->documentMessages());

        $result = DB::transaction(function () use ($request, $step1, $step2) {
            $companyCode = $this->generateUniqueCompanyCode3($step2['company_name']);
            $username = $this->nextUsernameForCompanyCode($companyCode);

            $logoPublicPath = null;
            $logoTempPath = $step2['logo_temp_path'] ?? null;

            if ($logoTempPath && Storage::disk('local')->exists($logoTempPath)) {
                $ext = pathinfo($logoTempPath, PATHINFO_EXTENSION);
                $logoPublicPath = 'company-logos/' . Str::uuid() . '.' . $ext;

                Storage::disk('public')->put(
                    $logoPublicPath,
                    Storage::disk('local')->get($logoTempPath)
                );

                Storage::disk('local')->delete($logoTempPath);
            }

            $company = Company::create([
                'company_name' => $step2['company_name'],
                'company_code' => $companyCode,
                'company_email' => $step2['company_email'],
                'company_email_verified_at' => now(),
                'company_phone' => $step2['company_phone'],
                'company_address' => $step2['company_address'],
                'business_type' => $step2['business_type'],
                'registration_number' => $step2['registration_number'],
                'authorized_representative_name' => $step2['authorized_representative_name'],
                'authorized_representative_position' => $step2['authorized_representative_position'],
                'authorized_representative_contact' => $step2['authorized_representative_contact'],
                'logo' => $logoPublicPath,
                'status' => 'for_verification',
                'created_by' => null,
            ]);

            $user = User::create([
                'name' => $step1['name'],
                'email' => $step1['email'],
                'email_verified_at' => now(),
                'phone_number' => $step1['phone'],
                'password' => Hash::make($step1['password']),
                'username' => $username,
                'company_id' => $company->id,
                'status' => 'active',
                'must_change_password' => false,
            ]);

            $this->assignDispatcherRole($user);
            $company->update(['created_by' => $user->id]);

            $companySlug = $this->companySlugUpper($company->company_name);

            foreach ($request->input('documents', []) as $docType => $docData) {
                $fileKey = "documents.{$docType}.file";

                if (! $request->hasFile($fileKey)) {
                    continue;
                }

                $file = $request->file($fileKey);
                $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

                $path = $file->storeAs(
                    "company-documents/{$company->id}/{$docType}",
                    Str::uuid() . '.' . $ext,
                    'public'
                );

                $uniformOriginal = $this->nextUniformOriginalName(
                    companyId: $company->id,
                    base: "{$companySlug}_{$docType}",
                    ext: $ext
                );

                CompanyDocument::create([
                    'company_id' => $company->id,
                    'doc_type' => $docType,
                    'file_path' => $path,
                    'original_name' => $uniformOriginal,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'issued_at' => $docData['issued_at'] ?? null,
                    'expires_at' => $docData['expires_at'] ?? null,
                    'status' => 'pending',
                    'remarks' => null,
                    'uploaded_by' => $user->id,
                ]);
            }

            return [
                'user' => $user,
                'company' => $company,
            ];
        });

        $user = $result['user'];
        $company = $result['company'];

        $this->notificationService->notifyInternalUsers(
            new NewCompanySubmittedNotification($company, $user),
            ['super-admin', 'admin', 'terminal manager']
        );

        $submissionNotification = new CompanySubmissionReceivedNotification($company);

        $this->notificationService->notifyCompanyUsers($company, $submissionNotification);
        $this->notificationService->notifyCompanyEmail($company, $submissionNotification);

        $request->session()->forget('registration');
        Auth::login($user);

        return redirect()->route('registration.status');
    }

    public function status(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $company = $user?->company;

        if (! $user) {
            return redirect()->route('company-registration.show');
        }

        if (! $company) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('company-registration.show');
        }

        $this->companyStatusService->markExpiredDocumentsAndSync(collect([$company]));
        $this->companyStatusService->syncCompanyStatus($company);
        $company = $company->fresh();

        if ($company->status === 'verified') {
            return redirect()->route('company.dashboard');
        }

        $meta = [
            'draft' => [
                'title' => 'Registration Incomplete',
                'description' => 'Your registration was not completed. Please contact support.',
                'icon' => 'draft',
                'color' => 'muted',
            ],
            'for_verification' => [
                'title' => 'Under Review',
                'description' => 'Your documents have been submitted and are currently under review. Verification typically takes 2–3 business days. We’ll notify you once the process is complete.',
                'icon' => 'clock',
                'color' => 'warning',
            ],
            'needs_revision' => [
                'title' => 'Action Required',
                'description' => 'One or more documents are invalid or expired. Please review the details below and resubmit the required files.',
                'icon' => 'warning',
                'color' => 'destructive',
            ],
            'verified' => [
                'title' => 'Approved',
                'description' => 'Your company documents have been approved.',
                'icon' => 'check',
                'color' => 'success',
            ],
        ];

        return Inertia::render('RegistrationStatus', [
            'company' => [
                'id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'company_email' => $company->company_email,
                'company_email_verified_at' => $company->company_email_verified_at,
                'is_company_email_verified' => $company->hasVerifiedCompanyEmail(),
                'status' => $company->status,
                'documents' => $company->documents()
                    ->select(['id', 'doc_type', 'status', 'remarks', 'original_name', 'expires_at'])
                    ->get(),
            ],
            'meta' => $meta[$company->status] ?? $meta['for_verification'],
        ]);
    }

    public function storeResubmission(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user?->company;

        if (! $user || ! $company) {
            return redirect()->route('company-registration.show');
        }

        if ($company->status !== 'needs_revision') {
            return redirect()->route('registration.status');
        }

        $actionRequiredDocs = $company->documents()
            ->whereIn('status', ['invalid', 'expired'])
            ->get();

        if ($actionRequiredDocs->isEmpty()) {
            return redirect()->route('registration.status');
        }

        $rules = [];
        foreach ($actionRequiredDocs as $doc) {
            $t = $doc->doc_type;

            $rules["documents.$t.file"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $rules["documents.$t.issued_at"] = ['required', 'date', 'before_or_equal:today'];
            $rules["documents.$t.expires_at"] = ['required', 'date', 'after_or_equal:today', "after:documents.$t.issued_at"];
        }

        $request->validate($rules, $this->documentMessages());

        DB::transaction(function () use ($request, $user, $company, $actionRequiredDocs) {
            $disk = Storage::disk('public');
            $companySlug = $this->companySlugUpper($company->company_name);

            foreach ($actionRequiredDocs as $doc) {
                $type = $doc->doc_type;
                $fileKey = "documents.$type.file";

                if (! $request->hasFile($fileKey)) {
                    continue;
                }

                $file = $request->file($fileKey);
                $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

                $newPath = $file->storeAs(
                    "company-documents/{$company->id}/{$type}",
                    Str::uuid() . '.' . $ext,
                    'public'
                );

                $duplicateDocs = CompanyDocument::query()
                    ->where('company_id', $company->id)
                    ->where('doc_type', $type)
                    ->where('id', '!=', $doc->id)
                    ->get();

                foreach ($duplicateDocs as $duplicateDoc) {
                    if ($duplicateDoc->file_path && $disk->exists($duplicateDoc->file_path)) {
                        $disk->delete($duplicateDoc->file_path);
                    }

                    $duplicateDoc->delete();
                }

                if ($doc->file_path && $disk->exists($doc->file_path)) {
                    $disk->delete($doc->file_path);
                }

                $uniformOriginal = $this->nextUniformOriginalName(
                    companyId: $company->id,
                    base: "{$companySlug}_{$type}",
                    ext: $ext
                );

                $doc->update([
                    'file_path' => $newPath,
                    'original_name' => $uniformOriginal,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'issued_at' => data_get($request->input('documents'), "$type.issued_at"),
                    'expires_at' => data_get($request->input('documents'), "$type.expires_at"),
                    'status' => 'pending',
                    'remarks' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                    'uploaded_by' => $user->id,
                ]);
            }

            $company->update(['status' => 'for_verification']);
        });

        $company->refresh();

        $this->notificationService->notifyInternalUsers(
            new CompanyResubmittedNotification($company, $user),
            ['super-admin', 'admin', 'terminal manager']
        );

        $resubmittedNotification = new CompanyResubmittedReceivedNotification($company);

        $this->notificationService->notifyCompanyUsers($company, $resubmittedNotification);
        $this->notificationService->notifyCompanyEmail($company, $resubmittedNotification);

        return redirect()
            ->route('registration.status')
            ->with('status', 'Your resubmitted documents were sent for review.');
    }

    private function normalizePhMobile(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $phone = preg_replace('/\s+/', '', trim($raw));

        if (preg_match('/^09\d{9}$/', $phone)) {
            return '+63' . substr($phone, 1);
        }

        if (preg_match('/^639\d{9}$/', $phone)) {
            return '+' . $phone;
        }

        return $phone;
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function documentMessages(): array
    {
        $labels = [
            'AUTHORIZATION_LETTER' => 'Authorization Letter',
            'SEC_CERT' => 'SEC Certificate',
            'DTI_CERT' => 'DTI Certificate',
            'MAYORS_PERMIT' => "Mayor's Permit",
            'BIR_2303' => 'BIR Form 2303',
        ];

        $messages = [];
        foreach ($labels as $key => $label) {
            $messages["documents.{$key}.file.required"] = "{$label} file is required.";
            $messages["documents.{$key}.file.required_with"] = "{$label} file is required when issue or expiry date is provided.";
            $messages["documents.{$key}.file.mimes"] = "{$label} must be a PDF, JPG, or PNG.";
            $messages["documents.{$key}.file.max"] = "{$label} must not exceed 5 MB.";
            $messages["documents.{$key}.issued_at.required"] = "{$label}: issue date is required.";
            $messages["documents.{$key}.issued_at.required_with"] = "{$label}: issue date is required when a file is uploaded.";
            $messages["documents.{$key}.issued_at.date"] = "{$label}: issue date must be a valid date.";
            $messages["documents.{$key}.issued_at.before_or_equal"] = "{$label}: issue date cannot be in the future.";
            $messages["documents.{$key}.expires_at.required"] = "{$label}: expiry date is required.";
            $messages["documents.{$key}.expires_at.required_with"] = "{$label}: expiry date is required when a file is uploaded.";
            $messages["documents.{$key}.expires_at.after"] = "{$label}: expiry date must be after the issue date.";
            $messages["documents.{$key}.expires_at.after_or_equal"] = "{$label}: expiry date must be today or later.";
        }

        return $messages;
    }

    private function generateUniqueCompanyCode3(string $companyName): string
    {
        $letters = strtoupper(preg_replace('/[^A-Z]/i', '', $companyName));
        $letters = str_pad($letters, 3, 'X');
        $candidates = [substr($letters, 0, 3)];

        for ($i = 1; $i <= strlen($letters) - 3; $i++) {
            $candidates[] = substr($letters, $i, 3);
        }

        foreach (array_unique($candidates) as $code) {
            if (! Company::where('company_code', $code)->exists()) {
                return $code;
            }
        }

        $hash = strtoupper(md5($letters));
        for ($i = 0; $i < strlen($hash) - 2; $i++) {
            $code = substr($hash, $i, 3);
            if (! Company::where('company_code', $code)->exists()) {
                return $code;
            }
        }

        do {
            $code = strtoupper(Str::random(3));
        } while (Company::where('company_code', $code)->exists());

        return $code;
    }

    private function nextUsernameForCompanyCode(string $companyCode): string
    {
        $last = DB::table('users')
            ->where('username', 'like', $companyCode . '-%')
            ->orderByDesc('username')
            ->lockForUpdate()
            ->value('username');

        $next = 1;
        if (is_string($last) && preg_match('/-(\d{4})$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $companyCode . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function companySlugUpper(string $companyName): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]+/', '_', $companyName);
        return strtoupper(trim(preg_replace('/_+/', '_', $clean), '_') ?: 'COMPANY');
    }

    private function nextUniformOriginalName(int $companyId, string $base, string $ext): string
    {
        $base = strtoupper($base);
        $name = "{$base}.{$ext}";

        if (! CompanyDocument::where('company_id', $companyId)->where('original_name', $name)->exists()) {
            return $name;
        }

        for ($i = 2; ; $i++) {
            $candidate = "{$base}_{$i}.{$ext}";
            if (! CompanyDocument::where('company_id', $companyId)->where('original_name', $candidate)->exists()) {
                return $candidate;
            }
        }
    }

    private function assignDispatcherRole(User $user): void
    {
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('operator');
            return;
        }

        if (Schema::hasColumn('users', 'role')) {
            $user->forceFill(['role' => 'operator'])->saveQuietly();
        }
    }
}
