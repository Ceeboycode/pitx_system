<?php

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

it('sends the created and updated details to the company profile page', function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = User::factory()->create();
    $company = Company::factory()->create([
        'status' => Company::STATUS_VERIFIED,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $permission = Permission::query()->firstOrCreate(['name' => 'external_companies_settings.view', 'guard_name' => 'web']);
    $role = Role::query()->create(['name' => 'profile-viewer', 'guard_name' => 'web', 'type' => 'external']);
    $role->givePermissionTo($permission);

    $user = User::factory()->external($company->id)->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Settings/CompanyProfile')
            ->where('company.creator.name', $admin->name)
            ->where('company.updater.name', $admin->name)
            ->has('company.created_at_human')
            ->has('company.updated_at_human'));
});
