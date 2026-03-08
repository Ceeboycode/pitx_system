<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompaniesExport implements FromArray, WithHeadings
{
    public function __construct(
        protected Collection $companies
    ) {}

    public function headings(): array
    {
        return [
            'company_id',
            'company_name',
            'company_code',
            'company_email',
            'company_phone',
            'company_address',
            'business_type',
            'registration_number',
            'authorized_representative_name',
            'authorized_representative_position',
            'authorized_representative_contact',
            'status',
            'created_at',
            'created_by_name',
            'created_by_email',

            'users_count',
            'user_names',
            'user_emails',
            'user_usernames',
            'user_phone_numbers',
            'user_password_hash',

            'documents_count',
            'document_types',
            'document_original_names',
            'document_statuses',
            'document_issued_dates',
            'document_expiry_dates',
            'document_remarks',
        ];
    }

    public function array(): array
    {
        return $this->companies->map(function ($company) {
            return [
                'company_id' => $company->id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'company_email' => $company->company_email,
                'company_phone' => $company->company_phone,
                'company_address' => $company->company_address,
                'business_type' => $company->business_type,
                'registration_number' => $company->registration_number,
                'authorized_representative_name' => $company->authorized_representative_name,
                'authorized_representative_position' => $company->authorized_representative_position,
                'authorized_representative_contact' => $company->authorized_representative_contact,
                'status' => $company->status,
                'created_at' => $company->created_at?->toDateTimeString(),
                'created_by_name' => $company->creator?->name,
                'created_by_email' => $company->creator?->email,

                'users_count' => $company->users->count(),
                'user_names' => $company->users->pluck('name')->filter()->implode(' | '),
                'user_emails' => $company->users->pluck('email')->filter()->implode(' | '),
                'user_usernames' => $company->users->pluck('username')->filter()->implode(' | '),
                'user_phone_numbers' => $company->users->pluck('phone_number')->filter()->implode(' | '),
                'user_password_hash' => $company->users->pluck('password')->filter()->implode(' | '),










                'documents_count' => $company->documents->count(),
                'document_types' => $company->documents->pluck('doc_type')->filter()->implode(' | '),
                'document_original_names' => $company->documents->pluck('original_name')->filter()->implode(' | '),
                'document_statuses' => $company->documents->pluck('status')->filter()->implode(' | '),
                'document_issued_dates' => $company->documents
                    ->map(fn ($doc) => $doc->issued_at?->toDateString())
                    ->filter()
                    ->implode(' | '),
                'document_expiry_dates' => $company->documents
                    ->map(fn ($doc) => $doc->expires_at?->toDateString())
                    ->filter()
                    ->implode(' | '),
                'document_remarks' => $company->documents->pluck('remarks')->filter()->implode(' | '),
            ];
        })->values()->all();
    }
}
