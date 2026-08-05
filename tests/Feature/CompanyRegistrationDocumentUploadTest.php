<?php

use App\Http\Middleware\EnsureRoleType;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->withoutMiddleware(EnsureRoleType::class);
    Notification::fake();
    Storage::fake('public');
});

it('accepts docx registration resubmissions and stores optional supporting documents', function (): void {
    $company = Company::factory()->create([
        'company_name' => 'Acme Transit',
        'status' => Company::STATUS_NEEDS_REVISION,
    ]);

    $user = User::factory()->external($company->id)->create();

    $oldPath = 'company-documents/old-permit.pdf';
    Storage::disk('public')->put($oldPath, 'old file');

    $document = CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => $oldPath,
        'status' => 'invalid',
        'remarks' => 'Blurry scan.',
        'uploaded_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('registration.resubmit.store'), [
        'documents' => [
            'MAYORS_PERMIT' => [
                'file' => UploadedFile::fake()->create(
                    'updated-permit.docx',
                    128,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ),
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ],
        ],
        'supporting_documents' => [
            [
                'title' => 'Board Resolution',
                'file' => UploadedFile::fake()->create('board-resolution.pdf', 96, 'application/pdf'),
            ],
        ],
    ]);

    $response->assertRedirect(route('registration.status'));

    $document->refresh();
    $supportingDocument = CompanyDocument::query()
        ->where('company_id', $company->id)
        ->where('doc_type', 'SUPPORTING_DOCUMENT')
        ->firstOrFail();

    expect($company->fresh()->status)->toBe(Company::STATUS_FOR_VERIFICATION)
        ->and($document->status)->toBe('pending')
        ->and($document->remarks)->toBeNull()
        ->and($document->original_name)->toEndWith('.docx')
        ->and($supportingDocument->remarks)->toBe('Supporting document: Board Resolution')
        ->and($supportingDocument->original_name)->toEndWith('.pdf')
        ->and(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('public')->exists($document->file_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($supportingDocument->file_path))->toBeTrue();
});

it('rejects unsupported registration document file extensions', function (): void {
    $company = Company::factory()->create([
        'status' => Company::STATUS_NEEDS_REVISION,
    ]);

    $user = User::factory()->external($company->id)->create();

    CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'status' => 'invalid',
        'uploaded_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->from(route('registration.status'))->post(route('registration.resubmit.store'), [
        'documents' => [
            'MAYORS_PERMIT' => [
                'file' => UploadedFile::fake()->create('permit.exe', 64, 'application/x-msdownload'),
                'issued_at' => now()->subMonth()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ],
        ],
    ]);

    $response
        ->assertRedirect(route('registration.status'))
        ->assertSessionHasErrors([
            'documents.MAYORS_PERMIT.file' => "Mayor's Permit must be a PDF, DOC, DOCX, JPG, or PNG file.",
        ]);
});

it('shows uploaded document preview metadata on the registration status page', function (): void {
    $company = Company::factory()->create([
        'status' => Company::STATUS_FOR_VERIFICATION,
    ]);

    $user = User::factory()->external($company->id)->create();

    $path = 'company-documents/'.$company->id.'/MAYORS_PERMIT/permit.pdf';
    Storage::disk('public')->put($path, '%PDF-1.4');

    $document = CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => $path,
        'original_name' => 'ACME_MAYORS_PERMIT.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 204800,
        'status' => 'pending',
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('registration.status'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CompanyRegistration')
            ->where('company.documents.0.id', $document->id)
            ->where('company.documents.0.original_name', 'ACME_MAYORS_PERMIT.pdf')
            ->where('company.documents.0.file_type', 'PDF')
            ->where('company.documents.0.can_preview', true)
            ->where('company.documents.0.preview_url', route('registration.documents.preview', $document, false))
            ->where('company.documents.0.download_url', route('registration.documents.download', $document, false)));
});

it('allows external users to preview their own uploaded registration documents', function (): void {
    $company = Company::factory()->create([
        'status' => Company::STATUS_FOR_VERIFICATION,
    ]);

    $user = User::factory()->external($company->id)->create();

    $path = 'company-documents/'.$company->id.'/MAYORS_PERMIT/permit.pdf';
    Storage::disk('public')->put($path, '%PDF-1.4');

    $document = CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => $path,
        'original_name' => 'ACME_MAYORS_PERMIT.pdf',
        'mime_type' => 'application/pdf',
        'status' => 'pending',
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('registration.documents.preview', $document))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('downloads uploaded registration documents that cannot be previewed', function (): void {
    $company = Company::factory()->create([
        'status' => Company::STATUS_FOR_VERIFICATION,
    ]);

    $user = User::factory()->external($company->id)->create();

    $path = 'company-documents/'.$company->id.'/BIR_2303/certificate.docx';
    Storage::disk('public')->put($path, 'docx file');

    $document = CompanyDocument::factory()->create([
        'company_id' => $company->id,
        'doc_type' => 'BIR_2303',
        'file_path' => $path,
        'original_name' => 'ACME_BIR_2303.docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'status' => 'pending',
        'uploaded_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('registration.documents.download', $document))
        ->assertOk()
        ->assertDownload('ACME_BIR_2303.docx');
});

it('does not allow external users to preview documents from another company', function (): void {
    $company = Company::factory()->create([
        'status' => Company::STATUS_FOR_VERIFICATION,
    ]);
    $otherCompany = Company::factory()->create([
        'status' => Company::STATUS_FOR_VERIFICATION,
    ]);

    $user = User::factory()->external($company->id)->create();
    $otherUser = User::factory()->external($otherCompany->id)->create();

    $path = 'company-documents/'.$otherCompany->id.'/MAYORS_PERMIT/permit.pdf';
    Storage::disk('public')->put($path, '%PDF-1.4');

    $document = CompanyDocument::factory()->create([
        'company_id' => $otherCompany->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => $path,
        'original_name' => 'OTHER_MAYORS_PERMIT.pdf',
        'mime_type' => 'application/pdf',
        'status' => 'pending',
        'uploaded_by' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get(route('registration.documents.preview', $document))
        ->assertNotFound();
});
