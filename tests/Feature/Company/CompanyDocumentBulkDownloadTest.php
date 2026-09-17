<?php

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['companies.view', 'company_documents.viewAny', 'company_documents.download', 'company_documents.view'] as $perm) {
        Permission::query()->firstOrCreate([
            'name' => $perm,
            'guard_name' => 'web',
        ]);
    }

    Storage::fake('public');
});

function createAuthorizedUser(): User
{
    $role = Role::query()->create([
        'name' => fake()->unique()->slug(2),
        'guard_name' => 'web',
        'type' => 'internal',
    ]);

    $role->givePermissionTo(['companies.view', 'company_documents.viewAny', 'company_documents.download', 'company_documents.view']);

    $user = User::factory()->internal()->create();
    $user->assignRole($role);

    return $user;
}

it('downloads all verified documents in a zip with correct filename format', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-17 16:30:00', 'UTC'));

    $user = createAuthorizedUser();

    $company = Company::factory()->create([
        'company_code' => 'COMP001',
    ]);

    Storage::disk('public')->put('company-documents/doc1.pdf', 'Content 1');
    Storage::disk('public')->put('company-documents/doc2.pdf', 'Content 2');

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/doc1.pdf',
        'original_name' => 'mayors_permit.pdf',
        'status' => 'verified',
        'expires_at' => now()->addYear()->toDateString(),
    ]);

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'file_path' => 'company-documents/doc2.pdf',
        'original_name' => 'bir_2303.pdf',
        'status' => 'verified',
        'expires_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('companies.documents.downloadBulk', $company));

    $response->assertOk();

    $expectedFilename = 'COMP001_2026-09-17-1630_verified.zip';
    $disposition = (string) $response->headers->get('content-disposition');
    expect($disposition)->toContain($expectedFilename);

    ob_start();
    $response->sendContent();
    $zipContent = ob_get_clean();

    $tmpZip = tempnam(sys_get_temp_dir(), 'test_zip_');
    file_put_contents($tmpZip, $zipContent);

    $zip = new ZipArchive;
    expect($zip->open($tmpZip))->toBeTrue();
    expect($zip->locateName('mayors_permit.pdf'))->not()->toBeFalse()
        ->and($zip->locateName('bir_2303.pdf'))->not()->toBeFalse();
    $zip->close();
    @unlink($tmpZip);
});

it('uses the application configured timezone for the zip filename timestamp', function (): void {
    config(['app.timezone' => 'Asia/Manila']);
    Carbon::setTestNow(Carbon::create(2026, 9, 17, 16, 30, 0, 'Asia/Manila'));

    $user = createAuthorizedUser();

    $company = Company::factory()->create([
        'company_code' => 'PITX100',
    ]);

    Storage::disk('public')->put('company-documents/doc1.pdf', 'Content 1');

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/doc1.pdf',
        'original_name' => 'mayors_permit.pdf',
        'status' => 'verified',
        'expires_at' => now('Asia/Manila')->addMonth()->toDateString(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('companies.documents.downloadBulk', $company));

    $response->assertOk();
    $expectedFilename = 'PITX100_2026-09-17-1630_verified.zip';
    expect((string) $response->headers->get('content-disposition'))->toContain($expectedFilename);
});

it('sanitizes unsafe company code characters for filename', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-17 08:05:00', 'UTC'));

    $user = createAuthorizedUser();

    $company = Company::factory()->create([
        'company_code' => 'COMP/001:TEST*?<bad>|',
    ]);

    Storage::disk('public')->put('company-documents/doc1.pdf', 'Content 1');

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/doc1.pdf',
        'original_name' => 'mayors_permit.pdf',
        'status' => 'verified',
        'expires_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('companies.documents.downloadBulk', $company));

    $response->assertOk();
    $expectedFilename = 'COMP_001_TEST_bad_2026-09-17-0805_verified.zip';
    expect((string) $response->headers->get('content-disposition'))->toContain($expectedFilename);
});

it('falls back to company id when company code sanitizes to empty', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-17 10:00:00', 'UTC'));

    $user = createAuthorizedUser();

    $company = Company::factory()->create([
        'company_code' => '///:::***???',
    ]);

    Storage::disk('public')->put('company-documents/doc1.pdf', 'Content 1');

    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/doc1.pdf',
        'original_name' => 'mayors_permit.pdf',
        'status' => 'verified',
        'expires_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('companies.documents.downloadBulk', $company));

    $response->assertOk();
    $expectedFilename = "COMPANY-{$company->id}_2026-09-17-1000_verified.zip";
    expect((string) $response->headers->get('content-disposition'))->toContain($expectedFilename);
});

it('includes only genuinely verified documents and excludes expired or invalid ones', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-17 12:00:00', 'UTC'));

    $user = createAuthorizedUser();

    $company = Company::factory()->create([
        'company_code' => 'COMP002',
    ]);

    Storage::disk('public')->put('company-documents/verified.pdf', 'Verified Content');
    Storage::disk('public')->put('company-documents/expired_verified.pdf', 'Expired Verified Content');
    Storage::disk('public')->put('company-documents/invalid.pdf', 'Invalid Content');
    Storage::disk('public')->put('company-documents/pending.pdf', 'Pending Content');

    // Valid verified doc
    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/verified.pdf',
        'original_name' => 'verified_permit.pdf',
        'status' => 'verified',
        'expires_at' => '2026-12-31',
    ]);

    // Expired doc that has status = 'verified' in DB (cron has not run yet)
    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'file_path' => 'company-documents/expired_verified.pdf',
        'original_name' => 'expired_bir.pdf',
        'status' => 'verified',
        'expires_at' => '2026-09-10', // in past
    ]);

    // Invalid doc
    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'SEC_CERT',
        'file_path' => 'company-documents/invalid.pdf',
        'original_name' => 'invalid_sec.pdf',
        'status' => 'invalid',
        'remarks' => 'Blurry scan',
        'expires_at' => '2027-01-01',
    ]);

    // Pending doc
    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'DTI_CERT',
        'file_path' => 'company-documents/pending.pdf',
        'original_name' => 'pending_dti.pdf',
        'status' => 'pending',
        'expires_at' => '2027-01-01',
    ]);

    $response = $this->actingAs($user)
        ->post(route('companies.documents.downloadBulk', $company));

    $response->assertOk();

    ob_start();
    $response->sendContent();
    $zipContent = ob_get_clean();

    $tmpZip = tempnam(sys_get_temp_dir(), 'test_zip_');
    file_put_contents($tmpZip, $zipContent);

    $zip = new ZipArchive;
    expect($zip->open($tmpZip))->toBeTrue();
    expect($zip->locateName('verified_permit.pdf'))->not()->toBeFalse()
        ->and($zip->locateName('expired_bir.pdf'))->toBeFalse()
        ->and($zip->locateName('invalid_sec.pdf'))->toBeFalse()
        ->and($zip->locateName('pending_dti.pdf'))->toBeFalse();
    $zip->close();
    @unlink($tmpZip);
});

it('aborts with 404 when company has no verified documents', function (): void {
    $user = createAuthorizedUser();

    $company = Company::factory()->create([
        'company_code' => 'COMP003',
    ]);

    // Only expired/invalid/pending docs
    CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/doc.pdf',
        'original_name' => 'mayors_permit.pdf',
        'status' => 'pending',
        'expires_at' => '2027-01-01',
    ]);

    $response = $this->actingAs($user)
        ->post(route('companies.documents.downloadBulk', $company));

    $response->assertNotFound();
});

it('forbids unauthorized users from downloading verified documents zip', function (): void {
    $company = Company::factory()->create([
        'company_code' => 'COMP004',
    ]);

    // Guest request
    $this->post(route('companies.documents.downloadBulk', $company))
        ->assertRedirect(route('login'));

    // User without required permissions
    $unauthorizedRole = Role::query()->create([
        'name' => 'unauthorized-role',
        'guard_name' => 'web',
        'type' => 'internal',
    ]);
    $unauthorizedUser = User::factory()->internal()->create();
    $unauthorizedUser->assignRole($unauthorizedRole);

    $this->actingAs($unauthorizedUser)
        ->post(route('companies.documents.downloadBulk', $company))
        ->assertForbidden();
});
