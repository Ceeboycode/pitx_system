<?php

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::query()->firstOrCreate([
        'name' => 'companies.update',
        'guard_name' => 'web',
    ]);
});

function internalUserWithCompanyPermissions(array $permissions = []): User
{
    $role = Role::query()->create([
        'name' => fake()->unique()->slug(2),
        'guard_name' => 'web',
        'type' => 'internal',
    ]);

    foreach ($permissions as $permission) {
        $role->givePermissionTo($permission);
    }

    $user = User::factory()->internal()->create();
    $user->assignRole($role);

    return $user;
}

it('updates only the company active status from the company edit action', function (): void {
    $user = internalUserWithCompanyPermissions(['companies.update']);

    $company = Company::factory()->create([
        'company_name' => 'ABC Transport',
        'status' => Company::STATUS_VERIFIED,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('companies.update', $company), [
            'company_name' => 'Forged Company Name',
            'status' => Company::STATUS_NEEDS_REVISION,
            'is_active' => false,
        ])
        ->assertRedirect();

    $company->refresh();

    expect($company->company_name)->toBe('ABC Transport')
        ->and($company->status)->toBe(Company::STATUS_VERIFIED)
        ->and($company->is_active)->toBeFalse()
        ->and($company->updated_by)->toBe($user->id);
});

it('forbids users without company update permission from changing active status', function (): void {
    $user = internalUserWithCompanyPermissions();

    $company = Company::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('companies.update', $company), [
            'is_active' => false,
        ])
        ->assertForbidden();

    expect($company->fresh()->is_active)->toBeTrue();
});
