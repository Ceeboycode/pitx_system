<?php

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['companies.view', 'companies.viewAny', 'company_documents.viewAny', 'company_documents.view', 'company_documents.download', 'company_documents.verify'] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Storage::fake('public');
});

/**
 * @param  array<int, string>  $permissions
 */
function archivedDocumentViewer(array $permissions): User
{
    $role = Role::query()->create([
        'name' => fake()->unique()->slug(2),
        'guard_name' => 'web',
        'type' => 'internal',
    ]);
    $role->givePermissionTo($permissions);

    $user = User::factory()->internal()->create();
    $user->assignRole($role);

    return $user;
}

function archivedCompanyWithVerifiedDocument(): array
{
    $company = Company::factory()->create();

    Storage::disk('public')->put('company-documents/permit.pdf', 'permit');

    $document = CompanyDocument::query()->create([
        'company_id' => $company->id,
        'doc_type' => 'MAYORS_PERMIT',
        'file_path' => 'company-documents/permit.pdf',
        'original_name' => 'permit.pdf',
        'status' => 'verified',
    ]);

    $company->delete();

    return [$company, $document];
}

const ARCHIVE_READER_PERMISSIONS = ['companies.view', 'companies.viewAny', 'company_documents.viewAny', 'company_documents.view', 'company_documents.download'];

it('downloads a document of an archived company for someone who may open the archives', function (): void {
    [$company, $document] = archivedCompanyWithVerifiedDocument();

    $this->actingAs(archivedDocumentViewer(ARCHIVE_READER_PERMISSIONS))
        ->get(route('companies.documents.download', [$company, $document]))
        ->assertOk()
        ->assertDownload('permit.pdf');
});

it('downloads the verified ZIP of an archived company for someone who may open the archives', function (): void {
    [$company] = archivedCompanyWithVerifiedDocument();

    $this->actingAs(archivedDocumentViewer(ARCHIVE_READER_PERMISSIONS))
        ->post(route('companies.documents.downloadBulk', $company))
        ->assertOk();
});

it('refuses document downloads of an archived company to someone who may not open the archives', function (): void {
    [$company, $document] = archivedCompanyWithVerifiedDocument();
    $viewer = archivedDocumentViewer(['companies.view', 'company_documents.viewAny', 'company_documents.view', 'company_documents.download']);

    $this->actingAs($viewer)
        ->get(route('companies.documents.download', [$company, $document]))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('companies.documents.downloadBulk', $company))
        ->assertForbidden();
});

it('still refuses to change the documents of an archived company', function (): void {
    [$company, $document] = archivedCompanyWithVerifiedDocument();
    $reviewer = archivedDocumentViewer([...ARCHIVE_READER_PERMISSIONS, 'company_documents.verify']);

    $this->actingAs($reviewer)
        ->patch(route('companies.documents.verify', [$company, $document]))
        ->assertNotFound();
});
