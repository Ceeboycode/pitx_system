<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $company = $user?->company()->with('documents')->first();

        if (! $company) {
            return Inertia::render('RegistrationStatus', [
                'company' => null,
                'meta' => [
                    'title'       => 'No Company Found',
                    'description' => 'Your account is missing a company record. Please register again.',
                    'icon'        => 'warning',
                    'color'       => 'destructive',
                ],
            ]);
        }

        // ── Resolve logo URL ────────────────────────────────────────────────
        // $company->logo stores the relative path e.g. "company-logos/uuid.jpg"
        // Storage::disk('public')->url() converts it to the full public URL.
        // We guard with filled() so null/empty string both return null.
        $logoUrl = filled($company->logo)
            ? Storage::disk('public')->url($company->logo)
            : null;

        return Inertia::render('External/Dashboard', [
            'company' => [
                'id'                             => $company->id,
                'company_name'                   => $company->company_name,
                'company_code'                   => $company->company_code,
                'company_email'                  => $company->company_email,
                'company_phone'                  => $company->company_phone,
                'status'                         => $company->status,
                'business_type'                  => $company->business_type,
                'authorized_representative_name' => $company->authorized_representative_name,
                'logo_url'                       => $logoUrl,
            ],
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
            'stats' => [
                'total_dispatches'   => $company->dispatches()->count(),
                'pending_dispatches' => $company->dispatches()->where('status', 'pending')->count(),
                'total_documents'    => $company->documents()->count(),
                'verified_documents' => $company->documents()->where('status', 'verified')->count(),
            ],
        ]);
    }
}
