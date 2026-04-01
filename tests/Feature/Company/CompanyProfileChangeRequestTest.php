<?php

use App\Models\Company;
use App\Models\CompanyProfileChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('applies staged company profile updates only after approval', function (): void {
    $company = Company::factory()->create([
        'status' => Company::STATUS_VERIFIED,
        'company_name' => 'Alpha Transit',
        'company_phone' => '09111111111',
    ]);

    $requester = User::factory()->external($company->id)->create();
    $approver = User::factory()->internal()->create();

    $changeRequest = CompanyProfileChangeRequest::query()->create([
        'company_id' => $company->id,
        'requested_by' => $requester->id,
        'status' => CompanyProfileChangeRequest::STATUS_PENDING,
        'requested_values' => [
            'company_name' => 'Alpha Transit Updated',
            'company_phone' => '09999999999',
        ],
        'current_values' => [
            'company_name' => 'Alpha Transit',
            'company_phone' => '09111111111',
        ],
    ]);

    expect($company->fresh()->company_name)->toBe('Alpha Transit');

    $changeRequest->approve($approver);

    expect($company->fresh()->company_name)->toBe('Alpha Transit Updated')
        ->and($company->fresh()->company_phone)->toBe('09999999999')
        ->and($company->fresh()->status)->toBe(Company::STATUS_FOR_VERIFICATION)
        ->and($changeRequest->fresh()->status)->toBe(CompanyProfileChangeRequest::STATUS_APPROVED)
        ->and($changeRequest->fresh()->approved_by)->toBe($approver->id);
});

it('deletes staged logo file when profile change request is rejected', function (): void {
    Storage::fake('public');

    $company = Company::factory()->create([
        'logo' => null,
    ]);

    $requester = User::factory()->external($company->id)->create();
    $approver = User::factory()->internal()->create();

    $logoPath = 'company-logos/pending/reject-me.png';
    Storage::disk('public')->put($logoPath, 'test-content');

    $changeRequest = CompanyProfileChangeRequest::query()->create([
        'company_id' => $company->id,
        'requested_by' => $requester->id,
        'status' => CompanyProfileChangeRequest::STATUS_PENDING,
        'requested_values' => [
            'logo' => $logoPath,
        ],
        'current_values' => [
            'logo' => null,
        ],
    ]);

    $changeRequest->reject($approver, 'Logo is not compliant.');

    expect(Storage::disk('public')->exists($logoPath))->toBeFalse()
        ->and($changeRequest->fresh()->status)->toBe(CompanyProfileChangeRequest::STATUS_REJECTED)
        ->and($changeRequest->fresh()->rejection_reason)->toBe('Logo is not compliant.');
});
