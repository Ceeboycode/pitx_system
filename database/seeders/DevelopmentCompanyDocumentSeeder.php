<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use App\Services\Company\CompanyStatusService;
use Database\Seeders\Concerns\CreatesSeedPdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DevelopmentCompanyDocumentSeeder extends Seeder
{
    use CreatesSeedPdf;

    public function run(CompanyStatusService $companyStatusService): void
    {
        $verifierId = User::query()->where('username', '2026-0003')->value('id');

        foreach ($this->scenarios() as $companyCode => $documents) {
            $company = Company::query()->where('company_code', $companyCode)->first();
            $uploaderId = $company?->users()->role('operator')->value('id');

            if ($company === null || $uploaderId === null) {
                continue;
            }

            foreach ($documents as $document) {
                $this->seedDocument($company, $document, $uploaderId, $verifierId);
            }

            $companyStatusService->syncCompanyStatus($company->fresh());
        }
    }

    /**
     * @return array<string, array<int, array{type: string, status: string, expires_at: string|null, remarks: string|null}>>
     */
    private function scenarios(): array
    {
        $verified = $this->documents('verified', now()->addYear()->toDateString());

        return [
            'NORTHSTAR' => $verified,
            'PENDING' => $this->documents('pending', now()->addYear()->toDateString()),
            'INCOMPLETE' => array_slice($this->documents('pending', now()->addYear()->toDateString()), 0, 2),
            'ISSUE' => $this->documents('invalid', now()->addYear()->toDateString(), 'Registration number is not readable.'),
            'EXPIRED' => $this->documents('expired', now()->subDay()->toDateString(), 'Document has expired.'),
            'SUSPENDED' => $verified,
        ];
    }

    /**
     * @return array<int, array{type: string, status: string, expires_at: string|null, remarks: string|null}>
     */
    private function documents(string $status, ?string $expiresAt, ?string $remarks = null): array
    {
        return collect(['SEC_CERT', 'MAYORS_PERMIT', 'BIR_2303'])
            ->map(fn (string $type): array => [
                'type' => $type,
                'status' => $type === 'SEC_CERT' ? $status : 'verified',
                'expires_at' => $type === 'SEC_CERT' ? $expiresAt : now()->addYear()->toDateString(),
                'remarks' => $type === 'SEC_CERT' ? $remarks : null,
            ])
            ->all();
    }

    /**
     * @param  array{type: string, status: string, expires_at: string|null, remarks: string|null}  $scenario
     */
    private function seedDocument(Company $company, array $scenario, int $uploaderId, ?int $verifierId): void
    {
        $fileName = strtolower($company->company_code.'-'.$scenario['type']).'.pdf';
        $filePath = "seed-fixtures/companies/{$company->company_code}/{$fileName}";

        if (! Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->put($filePath, $this->seedPdf([
                'SAMPLE DEVELOPMENT DATA — NOT AN OFFICIAL DOCUMENT',
                $company->company_name,
                $scenario['type'],
            ]));
        }

        CompanyDocument::query()->updateOrCreate(
            ['company_id' => $company->id, 'doc_type' => $scenario['type']],
            [
                'file_path' => $filePath,
                'original_name' => $fileName,
                'mime_type' => 'application/pdf',
                'file_size' => Storage::disk('public')->size($filePath),
                'issued_at' => now()->subMonths(6)->toDateString(),
                'expires_at' => $scenario['expires_at'],
                'status' => $scenario['status'],
                'remarks' => $scenario['remarks'],
                'uploaded_by' => $uploaderId,
                'verified_by' => $scenario['status'] === 'verified' ? $verifierId : null,
                'verified_at' => $scenario['status'] === 'verified' ? now()->subDay() : null,
            ],
        );

    }
}
