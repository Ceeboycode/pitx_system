<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Show profile page
    |--------------------------------------------------------------------------
    */
    public function show(Request $request): Response
    {
        $user    = $request->user();
        $company = $user->company;

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
                    ? Storage::disk('public')->url($company->logo)
                    : null,
            ],
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update logo
    |--------------------------------------------------------------------------
    */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'logo.required' => 'Please select an image to upload.',
            'logo.image'    => 'The file must be an image.',
            'logo.mimes'    => 'Logo must be a JPG, PNG, or WebP file.',
            'logo.max'      => 'Logo must not exceed 2 MB.',
        ]);

        $user    = $request->user();
        $company = $user->company;

        // Delete the old logo from storage if one exists
        if (filled($company->logo) && Storage::disk('public')->exists($company->logo)) {
            Storage::disk('public')->delete($company->logo);
        }

        // Store the new logo
        $file           = $request->file('logo');
        $ext            = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $logoPublicPath = 'company-logos/' . Str::uuid() . '.' . $ext;

        Storage::disk('public')->put(
            $logoPublicPath,
            file_get_contents($file->getRealPath())
        );

        $company->update(['logo' => $logoPublicPath]);

        return redirect()->back()->with('success', 'Logo updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Remove logo
    |--------------------------------------------------------------------------
    */
    public function removeLogo(Request $request): RedirectResponse
    {
        $user    = $request->user();
        $company = $user->company;

        if (filled($company->logo) && Storage::disk('public')->exists($company->logo)) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->update(['logo' => null]);

        return redirect()->back()->with('success', 'Logo removed.');
    }
}
