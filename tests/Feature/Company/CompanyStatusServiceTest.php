<?php

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Services\Company\CompanyStatusService;

it('marks expired company documents and syncs company status to needs_revision', function (): void {
    $company = Company::factory()->create([
        'business_type' => 'sole_proprietorship',
        'status' => Company::STATUS_VERIFIED,
    ]);

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/test-1.pdf',
        'original_name' => 'test-1.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'issued_at' => now()->subYear()->toDateString(),
        'expires_at' => now()->subDay()->toDateString(),
        'status' => 'verified',
    ]);

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'file_path' => 'company-documents/test-2.pdf',
        'original_name' => 'test-2.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'issued_at' => now()->subYear()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
        'status' => 'verified',
    ]);

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'DTI_CERT',
        'file_path' => 'company-documents/test-3.pdf',
        'original_name' => 'test-3.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1024,
        'issued_at' => now()->subYear()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
        'status' => 'verified',
    ]);

    $affected = app(CompanyStatusService::class)->markExpiredDocumentsAndSync();

    expect($affected)->toBe(1)
        ->and(CompanyDocument::query()->where('company_id', $company->id)->where('doc_type', 'MAYORS_PERMIT')->value('status'))
        ->toBe('expired')
        ->and($company->fresh()->status)
        ->toBe(Company::STATUS_NEEDS_REVISION);
});
