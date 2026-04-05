<?php

namespace App\Http\Requests\Company;

use App\Models\CompanyProfileChangeRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyProfileChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || $user->company_id === null) {
            return false;
        }

        $pendingExists = CompanyProfileChangeRequest::query()
            ->where('company_id', $user->company_id)
            ->where('status', CompanyProfileChangeRequest::STATUS_PENDING)
            ->exists();

        return ! $pendingExists;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'business_type' => ['nullable', 'in:corporate,sole_proprietorship'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'authorized_representative_name' => ['nullable', 'string', 'max:255'],
            'authorized_representative_position' => ['nullable', 'string', 'max:255'],
            'authorized_representative_contact' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'compliance_document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'compliance_document_issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'compliance_document_expires_at' => ['nullable', 'date', 'after:compliance_document_issued_at'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $company = $this->user()?->company;

            if (! $company) {
                return;
            }

            $newBusinessType = $this->input('business_type', $company->business_type);
            $currentBusinessType = $company->business_type;
            $businessTypeChanged = $newBusinessType !== $currentBusinessType;

            if (! $businessTypeChanged) {
                return;
            }

            $newRegistrationNumber = trim((string) $this->input('registration_number', (string) $company->registration_number));
            $currentRegistrationNumber = trim((string) $company->registration_number);
            $registrationNumberChanged = $newRegistrationNumber !== $currentRegistrationNumber;

            if (! $registrationNumberChanged) {
                $validator->errors()->add(
                    'registration_number',
                    'Registration number must also be updated when changing business type.'
                );
            }
        });
    }

    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You already have a pending profile update request under review.'
        );
    }
}
