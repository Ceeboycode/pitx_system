<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanyDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $verifier = User::role(['admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        $requiredDocs = [
            'business_permit',
            'bir_certificate',
            'dti_sec_registration',
            'mayors_permit',
            'company_insurance',
        ];

        Company::query()->get()->each(function (Company $company) use ($requiredDocs, $verifier) {
            $operator = User::query()
                ->where('company_id', $company->id)
                ->role('operator')
                ->first();

            $uploadedBy = $operator?->id ?? $verifier?->id;

            foreach ($requiredDocs as $docType) {
                CompanyDocument::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'doc_type' => $docType,
                    ],
                    [
                        'file_path' => "company-documents/{$company->id}/{$docType}.pdf",
                        'original_name' => "{$docType}.pdf",
                        'mime_type' => 'application/pdf',
                        'file_size' => fake()->numberBetween(100000, 3000000),
                        'issued_at' => now()->subMonths(rand(1, 12))->toDateString(),
                        'expires_at' => now()->addMonths(rand(3, 24))->toDateString(),
                        'status' => $company->status === 'verified' ? 'verified' : 'pending',
                        'remarks' => null,
                        'uploaded_by' => $uploadedBy,
                        'verified_by' => $company->status === 'verified' ? $verifier?->id : null,
                        'verified_at' => $company->status === 'verified' ? now()->subDays(rand(1, 30)) : null,
                    ]
                );
            }
        });
    }
}
