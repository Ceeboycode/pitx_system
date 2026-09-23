<?php

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Notification::fake();
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'dispatcher', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'it', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'terminal manager', 'guard_name' => 'web']);
});

it('creates supporting documents with pending status and preserves title in remarks', function (): void {
    $company = Company::factory()->create([
        'status' => Company::STATUS_DRAFT,
    ]);

    $user = User::factory()->external($company->id)->create();

    $sessionData = [
        'registration.step1' => [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '09171234567',
            'password_hash' => 'hash',
        ],
        'registration.step2' => [
            'company_name' => 'Test Transit Corp',
            'company_email' => 'corp@test.com',
            'company_phone' => '09181234567',
            'company_address' => '123 Test St, Barangay 1, City of Manila, NCR',
            'business_type' => 'corporate',
            'registration_number' => 'CS202012345',
            'authorized_representative_name' => 'Representative Name',
            'authorized_representative_position' => 'President',
            'authorized_representative_contact' => '09191234567',
            'logo_temp_path' => null,
        ],
        'registration.otp.account.verified' => true,
        'registration.otp.company.verified' => true,
    ];

    $response = $this->withSession($sessionData)->post(route('company-registration.storeStep3'), [
        'documents' => [
            'MAYORS_PERMIT' => [
                'file' => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ],
            'BIR_2303' => [
                'file' => UploadedFile::fake()->create('bir.pdf', 100, 'application/pdf'),
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ],
            'SEC_CERT' => [
                'file' => UploadedFile::fake()->create('sec.pdf', 100, 'application/pdf'),
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ],
        ],
        'supporting_documents' => [
            [
                'title' => 'Board Resolution 2026',
                'file' => UploadedFile::fake()->create('board-res.pdf', 100, 'application/pdf'),
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ],
        ],
    ]);

    $response->assertRedirect(route('registration.status'));

    $supportingDoc = CompanyDocument::query()
        ->where('doc_type', 'SUPPORTING_DOCUMENT')
        ->firstOrFail();

    expect($supportingDoc->status)->toBe('pending')
        ->and($supportingDoc->remarks)->toBe('Supporting document: Board Resolution 2026');
});

it('verifies document status changes between pending, verified, invalid, and expired', function (): void {
    $company = Company::factory()->create(['status' => Company::STATUS_FOR_VERIFICATION]);
    $admin = User::factory()->internal()->create();
    $admin->assignRole('super-admin');

    $doc = CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'pending',
    ]);

    expect($doc->status)->toBe('pending');

    // Reject -> invalid
    $this->actingAs($admin)->patch(route('companies.documents.reject', [$company, $doc]), [
        'remarks' => 'Blurry document.',
    ])->assertRedirect();

    expect($doc->fresh()->status)->toBe('invalid')
        ->and($doc->fresh()->remarks)->toBe('Blurry document.');

    // Verify -> verified
    $this->actingAs($admin)->patch(route('companies.documents.verify', [$company, $doc]))
        ->assertRedirect();

    expect($doc->fresh()->status)->toBe('verified')
        ->and($doc->fresh()->remarks)->toBeNull();

    // Unverify -> pending
    $this->actingAs($admin)->patch(route('companies.documents.unverify', [$company, $doc]))
        ->assertRedirect();

    expect($doc->fresh()->status)->toBe('pending');
});
