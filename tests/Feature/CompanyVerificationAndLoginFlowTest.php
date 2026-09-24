<?php

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Company\CompanyStatusService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Notification::fake();
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web'], ['type' => 'internal']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['type' => 'internal']);
    Role::firstOrCreate(['name' => 'it', 'guard_name' => 'web'], ['type' => 'internal']);
    Role::firstOrCreate(['name' => 'terminal manager', 'guard_name' => 'web'], ['type' => 'internal']);

    Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web'], ['type' => 'external']);
    Role::firstOrCreate(['name' => 'dispatcher', 'guard_name' => 'web'], ['type' => 'external']);
    Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web'], ['type' => 'external']);
    Role::firstOrCreate(['name' => 'commuter', 'guard_name' => 'web'], ['type' => 'external']);
});

test('operator can log in when a document is expired', function (): void {
    $company = Company::factory()->create([
        'business_type' => 'corporate',
        'is_active' => true,
    ]);

    $operator = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $operator->assignRole('operator');

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'expired',
        'expires_at' => now()->subDay()->toDateString(),
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'status' => 'verified',
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SEC_CERT',
        'status' => 'verified',
    ]);

    app(CompanyStatusService::class)->syncCompanyStatus($company);

    $response = $this->post(route('login.store'), [
        'login' => $operator->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($operator);
    $response->assertRedirect(route('registration.status'));
});

test('operator can log in when a document is invalid', function (): void {
    $company = Company::factory()->create([
        'business_type' => 'corporate',
        'is_active' => true,
    ]);

    $operator = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $operator->assignRole('operator');

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'invalid',
        'remarks' => 'Please upload a clearer copy.',
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'status' => 'verified',
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SEC_CERT',
        'status' => 'verified',
    ]);

    app(CompanyStatusService::class)->syncCompanyStatus($company);

    $response = $this->post(route('login.store'), [
        'login' => $operator->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($operator);
    $response->assertRedirect(route('registration.status'));
});

test('operator can log in when a supporting document is expired or invalid', function (): void {
    $company = Company::factory()->create([
        'business_type' => 'corporate',
        'is_active' => true,
    ]);

    $operator = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $operator->assignRole('operator');

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'verified',
    ]);
    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'status' => 'verified',
    ]);
    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SEC_CERT',
        'status' => 'verified',
    ]);

    // Supporting document is invalid
    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SUPPORTING_DOCUMENT',
        'status' => 'invalid',
        'remarks' => 'Supporting document: Franchise Copy',
    ]);

    app(CompanyStatusService::class)->syncCompanyStatus($company);
    expect($company->fresh()->status)->toBe('needs_revision');

    $response = $this->post(route('login.store'), [
        'login' => $operator->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($operator);
    $response->assertRedirect(route('registration.status'));
});

test('operator can resubmit an expired or invalid document and status becomes pending', function (): void {
    $company = Company::factory()->create([
        'company_name' => 'Metro Express Inc',
        'business_type' => 'corporate',
        'status' => 'needs_revision',
    ]);

    $operator = User::factory()->external($company->id)->create();
    $operator->assignRole('operator');

    $permit = CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'invalid',
        'remarks' => 'Image is blurry.',
    ]);

    $response = $this->actingAs($operator)
        ->from(route('registration.status'))
        ->post(route('registration.resubmit.store'), [
            'documents' => [
                'MAYORS_PERMIT' => [
                    'file' => UploadedFile::fake()->create('mayors-permit-2026.pdf', 200, 'application/pdf'),
                    'issued_at' => now()->subMonth()->toDateString(),
                    'expires_at' => now()->addYear()->toDateString(),
                ],
            ],
        ]);

    $response->assertRedirect(route('registration.status'));

    $permit->refresh();
    expect($permit->status)->toBe('pending')
        ->and($permit->remarks)->toBeNull()
        ->and($permit->verified_by)->toBeNull()
        ->and($permit->verified_at)->toBeNull()
        ->and(Storage::disk('public')->exists($permit->file_path))->toBeTrue();
});

test('operator can resubmit an expired or invalid supporting document', function (): void {
    $company = Company::factory()->create([
        'company_name' => 'Metro Express Inc',
        'business_type' => 'corporate',
        'status' => 'needs_revision',
    ]);

    $operator = User::factory()->external($company->id)->create();
    $operator->assignRole('operator');

    $supportingDoc = CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SUPPORTING_DOCUMENT',
        'status' => 'invalid',
        'remarks' => 'Supporting document: LTFRB CPC',
    ]);

    $response = $this->actingAs($operator)
        ->from(route('registration.status'))
        ->post(route('registration.resubmit.store'), [
            'documents' => [
                (string) $supportingDoc->id => [
                    'file' => UploadedFile::fake()->create('cpc-replacement.pdf', 200, 'application/pdf'),
                    'issued_at' => now()->subMonth()->toDateString(),
                    'expires_at' => now()->addYear()->toDateString(),
                ],
            ],
        ]);

    $response->assertRedirect(route('registration.status'));

    $supportingDoc->refresh();
    expect($supportingDoc->status)->toBe('pending')
        ->and($supportingDoc->remarks)->toBe('Supporting document: LTFRB CPC')
        ->and($supportingDoc->verified_by)->toBeNull()
        ->and($supportingDoc->verified_at)->toBeNull();
});

test('driver cannot log in while required documents are pending, invalid, or expired', function (string $docStatus): void {
    $company = Company::factory()->create([
        'business_type' => 'corporate',
        'is_active' => true,
    ]);

    $driver = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $driver->assignRole('driver');

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => $docStatus,
        'expires_at' => $docStatus === 'expired' ? now()->subDay()->toDateString() : now()->addYear()->toDateString(),
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'status' => 'verified',
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SEC_CERT',
        'status' => 'verified',
    ]);

    app(CompanyStatusService::class)->syncCompanyStatus($company);

    $this->post(route('login.store'), [
        'login' => $driver->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
})->with(['pending', 'invalid', 'expired']);

test('dispatcher cannot log in while required documents are pending, invalid, or expired', function (string $docStatus): void {
    $company = Company::factory()->create([
        'business_type' => 'corporate',
        'is_active' => true,
    ]);

    $dispatcher = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $dispatcher->assignRole('dispatcher');

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => $docStatus,
        'expires_at' => $docStatus === 'expired' ? now()->subDay()->toDateString() : now()->addYear()->toDateString(),
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'status' => 'verified',
    ]);

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SEC_CERT',
        'status' => 'verified',
    ]);

    app(CompanyStatusService::class)->syncCompanyStatus($company);

    $this->post(route('login.store'), [
        'login' => $dispatcher->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
})->with(['pending', 'invalid', 'expired']);

test('other external roles are blocked while company is not fully verified', function (): void {
    $company = Company::factory()->create([
        'business_type' => 'corporate',
        'is_active' => true,
    ]);

    $commuter = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $commuter->assignRole('commuter');

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'pending',
    ]);

    app(CompanyStatusService::class)->syncCompanyStatus($company);

    $this->post(route('login.store'), [
        'login' => $commuter->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('all external roles regain normal access once all required documents are verified', function (): void {
    $company = Company::factory()->create([
        'business_type' => 'corporate',
        'is_active' => true,
    ]);

    $driver = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $driver->assignRole('driver');

    $dispatcher = User::factory()->external($company->id)->create([
        'password' => 'password',
    ]);
    $dispatcher->assignRole('dispatcher');

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'verified',
    ]);
    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'status' => 'verified',
    ]);
    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'SEC_CERT',
        'status' => 'verified',
    ]);

    app(CompanyStatusService::class)->syncCompanyStatus($company);
    expect($company->fresh()->status)->toBe('verified');

    // Driver login
    $response = $this->post(route('login.store'), [
        'login' => $driver->email,
        'password' => 'password',
    ]);
    $this->assertAuthenticatedAs($driver);
    $response->assertRedirect(route('company.dashboard'));

    // Logout
    $this->post(route('logout'));
    $this->assertGuest();

    // Dispatcher login
    $response = $this->post(route('login.store'), [
        'login' => $dispatcher->email,
        'password' => 'password',
    ]);
    $this->assertAuthenticatedAs($dispatcher);
    $response->assertRedirect(route('company.dashboard'));
});

test('internal users are unaffected by company verification status', function (): void {
    $admin = User::factory()->internal()->create([
        'password' => 'password',
    ]);
    $admin->assignRole('admin');

    $response = $this->post(route('login.store'), [
        'login' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(route('dashboard'));
});
