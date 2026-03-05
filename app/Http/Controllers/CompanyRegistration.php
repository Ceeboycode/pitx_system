<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationOtpMail;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
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
    /*
    |--------------------------------------------------------------------------
    | Show wizard
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Step 1 – Validate account details → send OTP to personal email
    |--------------------------------------------------------------------------
    */
    public function storeStep1(Request $request): RedirectResponse
    {
        // Normalize phone BEFORE validate so unique works properly
        $request->merge([
            'phone' => $this->normalizePhMobile($request->input('phone')),
        ]);

        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'                 => [
                'required',
                'string',
                'max:20',
                'regex:/^\+639\d{9}$/',
                'unique:users,phone_number',
            ],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'email.unique'       => 'This email address is already registered.',
            'phone.regex'        => 'Phone number must be a valid PH mobile number (09XXXXXXXXX or +639XXXXXXXXX).',
            'phone.unique'       => 'This phone number is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        $otp = $this->generateOtp();

        $request->session()->put('registration.step1', [
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'],
            'password' => $validated['password'],
        ]);

        $request->session()->put('registration.otp.account', [
            'code'       => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified'   => false,
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
            return redirect()->back(303)->withErrors(['otp' => 'No code was sent. Please go back and retry.']);
        }

        if (now()->isAfter($stored['expires_at'])) {
            $request->session()->forget('registration.otp.account');
            return redirect()->back(303)->withErrors(['otp' => 'Code expired. Please request a new one.']);
        }

        if (! Hash::check($request->input('otp'), $stored['code'])) {
            return redirect()->back(303)->withErrors(['otp' => 'Invalid code. Please try again.']);
        }

        $request->session()->put('registration.otp.account.verified', true);

        return redirect()->back(303);
    }

    public function resendStep1Otp(Request $request): RedirectResponse
    {
        $step1 = $request->session()->get('registration.step1');

        if (! $step1) {
            return redirect()->back(303)->withErrors(['otp' => 'Session expired. Please start over.']);
        }

        $otp = $this->generateOtp();

        $request->session()->put('registration.otp.account', [
            'code'       => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified'   => false,
        ]);

        Mail::to($step1['email'])->send(
            new RegistrationOtpMail($otp, 'account', $step1['name'])
        );

        return redirect()->back(303)->with('resent', 'A new code has been sent to your email.');
    }

    /*
    |--------------------------------------------------------------------------
    | Step 2 – Validate company details → send OTP to company email
    |--------------------------------------------------------------------------
    */
    public function storeStep2(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'                       => ['required', 'string', 'max:255'],
            'company_email'                      => ['required', 'email', 'max:255'],
            'company_phone'                      => ['required', 'string', 'max:20'],
            'company_address'                    => ['required', 'string', 'max:500'],
            'business_type'                      => ['required', 'in:corporate,sole_proprietorship'],
            'registration_number'                => ['required', 'string', 'max:100'],
            'authorized_representative_name'     => ['required', 'string', 'max:255'],
            'authorized_representative_position' => ['required', 'string', 'max:255'],
            'authorized_representative_contact'  => ['required', 'string', 'max:20'],
        ], [
            'business_type.in' => 'Business type must be Corporate or Sole Proprietorship.',
        ]);

        $otp   = $this->generateOtp();
        $step1 = $request->session()->get('registration.step1');

        $request->session()->put('registration.step2', $validated);

        $request->session()->put('registration.otp.company', [
            'code'       => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified'   => false,
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
            return redirect()->back(303)->withErrors(['otp' => 'No code was sent. Please go back and retry.']);
        }

        if (now()->isAfter($stored['expires_at'])) {
            $request->session()->forget('registration.otp.company');
            return redirect()->back(303)->withErrors(['otp' => 'Code expired. Please request a new one.']);
        }

        if (! Hash::check($request->input('otp'), $stored['code'])) {
            return redirect()->back(303)->withErrors(['otp' => 'Invalid code. Please try again.']);
        }

        $request->session()->put('registration.otp.company.verified', true);

        return redirect()->back(303);
    }

    public function resendStep2Otp(Request $request): RedirectResponse
    {
        $step1 = $request->session()->get('registration.step1');
        $step2 = $request->session()->get('registration.step2');

        if (! $step2) {
            return redirect()->back(303)->withErrors(['otp' => 'Session expired. Please go back to step 2.']);
        }

        $otp = $this->generateOtp();

        $request->session()->put('registration.otp.company', [
            'code'       => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
            'verified'   => false,
        ]);

        Mail::to($step2['company_email'])->send(
            new RegistrationOtpMail($otp, 'company', $step1['name'] ?? $step2['company_name'])
        );

        return redirect()->back(303)->with('resent', 'A new code has been sent to your company email.');
    }

    /*
    |--------------------------------------------------------------------------
    | Step 3 – Documents → create company/user/docs
    |--------------------------------------------------------------------------
    */
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
            return redirect()->back(303)->withErrors(['session' => 'Please verify your account email before submitting.']);
        }

        if (! $request->session()->get('registration.otp.company.verified')) {
            return redirect()->back(303)->withErrors(['session' => 'Please verify your company email before submitting.']);
        }

        $isCorporate = $step2['business_type'] === 'corporate';

        $request->validate([
            'documents.MAYORS_PERMIT.file'       => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.MAYORS_PERMIT.issued_at'  => ['required', 'date', 'before_or_equal:today'],
            'documents.MAYORS_PERMIT.expires_at' => ['required', 'date', 'after:documents.MAYORS_PERMIT.issued_at'],

            'documents.BIR_2303.file'       => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.BIR_2303.issued_at'  => ['required', 'date', 'before_or_equal:today'],
            'documents.BIR_2303.expires_at' => ['required', 'date', 'after:documents.BIR_2303.issued_at'],

            'documents.AUTHORIZATION_LETTER.file'       => [$isCorporate ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.AUTHORIZATION_LETTER.issued_at'  => [$isCorporate ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'documents.AUTHORIZATION_LETTER.expires_at' => [$isCorporate ? 'required' : 'nullable', 'date', 'after:documents.AUTHORIZATION_LETTER.issued_at'],

            'documents.SEC_CERT.file'       => [$isCorporate ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.SEC_CERT.issued_at'  => [$isCorporate ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'documents.SEC_CERT.expires_at' => [$isCorporate ? 'required' : 'nullable', 'date', 'after:documents.SEC_CERT.issued_at'],

            'documents.DTI_CERT.file'       => [! $isCorporate ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.DTI_CERT.issued_at'  => [! $isCorporate ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'documents.DTI_CERT.expires_at' => [! $isCorporate ? 'required' : 'nullable', 'date', 'after:documents.DTI_CERT.issued_at'],
        ], $this->documentMessages());

        $user = DB::transaction(function () use ($request, $step1, $step2) {

            $companyCode = $this->generateUniqueCompanyCode3($step2['company_name']);
            $username    = $this->nextUsernameForCompanyCode($companyCode);

            $company = Company::create([
                'company_name'                       => $step2['company_name'],
                'company_code'                       => $companyCode,
                'company_email'                      => $step2['company_email'],
                'company_phone'                      => $step2['company_phone'],
                'company_address'                    => $step2['company_address'],
                'business_type'                      => $step2['business_type'],
                'registration_number'                => $step2['registration_number'],
                'authorized_representative_name'     => $step2['authorized_representative_name'],
                'authorized_representative_position' => $step2['authorized_representative_position'],
                'authorized_representative_contact'  => $step2['authorized_representative_contact'],
                'status'                             => 'for_verification',
                'created_by'                         => null,
            ]);

            $user = User::create([
                'name'         => $step1['name'],
                'email'        => $step1['email'],
                'phone_number' => $step1['phone'],
                'password'     => Hash::make($step1['password']),
                'username'     => $username,
                'company_id'   => $company->id,
            ]);

            $this->assignDispatcherRole($user);
            $company->update(['created_by' => $user->id]);

            $companySlug = $this->companySlugUpper($company->company_name);

            foreach ($request->input('documents', []) as $docType => $docData) {
                $fileKey = "documents.{$docType}.file";
                if (! $request->hasFile($fileKey)) continue;

                $file = $request->file($fileKey);
                $ext  = strtolower($file->getClientOriginalExtension() ?: 'bin');

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
                    'company_id'    => $company->id,
                    'doc_type'      => $docType,
                    'file_path'     => $path,
                    'original_name' => $uniformOriginal,
                    'mime_type'     => $file->getMimeType(),
                    'file_size'     => $file->getSize(),
                    'issued_at'     => $docData['issued_at'] ?? null,
                    'expires_at'    => $docData['expires_at'] ?? null,
                    'status'        => 'pending',
                    'remarks'       => null,
                    'uploaded_by'   => $user->id,
                ]);
            }

            return $user;
        });

        $request->session()->forget('registration');
        Auth::login($user);

        return redirect()->route('registration.status');
    }

    /*
    |--------------------------------------------------------------------------
    | Registration Status
    |--------------------------------------------------------------------------
    */
    public function status(Request $request): Response|RedirectResponse
    {
        $user    = $request->user();
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

        if ($company->status === 'verified') {
            return redirect()->route('company.dashboard');
        }

        $meta = [
            'draft' => [
                'title'       => 'Registration Incomplete',
                'description' => 'Your registration was not completed. Please contact support.',
                'icon'        => 'draft',
                'color'       => 'muted',
            ],
            'for_verification' => [
                'title'       => 'Under Review',
                'description' => 'Your documents have been submitted and are currently being reviewed by our team. We\'ll notify you once verified.',
                'icon'        => 'clock',
                'color'       => 'warning',
            ],
            'needs_revision' => [
                'title'       => 'Action Required',
                'description' => 'One or more documents were flagged. Please review the remarks below and resubmit the corrected files.',
                'icon'        => 'warning',
                'color'       => 'destructive',
            ],
        ];

        return Inertia::render('RegistrationStatus', [
            'company' => [
                'id'           => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'status'       => $company->status,
                'documents'    => $company->documents()
                    ->select(['id', 'doc_type', 'status', 'remarks', 'original_name'])
                    ->get(),
            ],
            'meta' => $meta[$company->status] ?? $meta['for_verification'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ NEW: Resubmit invalid documents (POST)
    |--------------------------------------------------------------------------
    | Called by inline uploader on RegistrationStatus.vue
    */
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

        $invalidDocs = $company->documents()->where('status', 'invalid')->get();

        if ($invalidDocs->isEmpty()) {
            return redirect()->route('registration.status');
        }

        // Validate only invalid docs
        $rules = [];
        foreach ($invalidDocs as $doc) {
            $t = $doc->doc_type;

            $rules["documents.$t.file"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $rules["documents.$t.issued_at"] = ['required', 'date', 'before_or_equal:today'];
            $rules["documents.$t.expires_at"] = ['required', 'date', "after:documents.$t.issued_at"];
        }

        $request->validate($rules, $this->documentMessages());

        DB::transaction(function () use ($request, $user, $company, $invalidDocs) {
            $disk = Storage::disk('public');
            $companySlug = $this->companySlugUpper($company->company_name);

            foreach ($invalidDocs as $doc) {
                $type = $doc->doc_type;
                $fileKey = "documents.$type.file";

                if (! $request->hasFile($fileKey)) {
                    continue;
                }

                $file = $request->file($fileKey);
                $ext  = strtolower($file->getClientOriginalExtension() ?: 'bin');

                $newPath = $file->storeAs(
                    "company-documents/{$company->id}/{$type}",
                    Str::uuid() . '.' . $ext,
                    'public'
                );

                // delete old file (optional)
                if ($doc->file_path && $disk->exists($doc->file_path)) {
                    $disk->delete($doc->file_path);
                }

                $uniformOriginal = $this->nextUniformOriginalName(
                    companyId: $company->id,
                    base: "{$companySlug}_{$type}",
                    ext: $ext
                );

                $doc->update([
                    'file_path'     => $newPath,
                    'original_name' => $uniformOriginal,
                    'mime_type'     => $file->getMimeType(),
                    'file_size'     => $file->getSize(),
                    'issued_at'     => data_get($request->input('documents'), "$type.issued_at"),
                    'expires_at'    => data_get($request->input('documents'), "$type.expires_at"),
                    'status'        => 'pending',
                    'remarks'       => null,
                    'uploaded_by'   => $user->id,
                ]);
            }

            // back to review queue
            $company->update(['status' => 'for_verification']);
        });

        return redirect()
            ->route('registration.status')
            ->with('status', 'Your corrected documents were submitted for review.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function normalizePhMobile(?string $raw): ?string
    {
        if ($raw === null) return null;

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
            'SEC_CERT'             => 'SEC Certificate',
            'DTI_CERT'             => 'DTI Certificate',
            'MAYORS_PERMIT'        => "Mayor's Permit",
            'BIR_2303'             => 'BIR Form 2303',
        ];

        $messages = [];
        foreach ($labels as $key => $label) {
            $messages["documents.{$key}.file.required"]       = "{$label} file is required.";
            $messages["documents.{$key}.file.mimes"]          = "{$label} must be a PDF, JPG, or PNG.";
            $messages["documents.{$key}.file.max"]            = "{$label} must not exceed 5 MB.";
            $messages["documents.{$key}.issued_at.required"]  = "{$label}: issue date is required.";
            $messages["documents.{$key}.issued_at.date"]      = "{$label}: issue date must be a valid date.";
            $messages["documents.{$key}.expires_at.required"] = "{$label}: expiry date is required.";
            $messages["documents.{$key}.expires_at.after"]    = "{$label}: expiry date must be after the issue date.";
        }

        return $messages;
    }

    private function generateUniqueCompanyCode3(string $companyName): string
    {
        $letters    = strtoupper(preg_replace('/[^A-Z]/i', '', $companyName));
        $letters    = str_pad($letters, 3, 'X');
        $candidates = [substr($letters, 0, 3)];

        for ($i = 1; $i <= strlen($letters) - 3; $i++) {
            $candidates[] = substr($letters, $i, 3);
        }

        foreach (array_unique($candidates) as $code) {
            if (! Company::where('company_code', $code)->exists()) return $code;
        }

        $hash = strtoupper(md5($letters));
        for ($i = 0; $i < strlen($hash) - 2; $i++) {
            $code = substr($hash, $i, 3);
            if (! Company::where('company_code', $code)->exists()) return $code;
        }

        do { $code = strtoupper(Str::random(3)); }
        while (Company::where('company_code', $code)->exists());

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
