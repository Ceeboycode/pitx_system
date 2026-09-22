<?php

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['operator', 'driver'] as $roleName) {
        Role::query()->firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['type' => 'external'],
        );
    }

    Permission::query()->firstOrCreate(['name' => 'external_users.create', 'guard_name' => 'web']);

    Role::query()->where('name', 'operator')->first()->syncPermissions(['external_users.create']);

    $this->company = Company::factory()->verified()->create();

    $this->operator = User::factory()->create(['company_id' => $this->company->id]);
    $this->operator->assignRole('operator');
});

test('operator sees the employee create page with the data the form needs', function (): void {
    $this->actingAs($this->operator)
        ->get(route('employee-users.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Employee/Create')
            ->where('company.id', $this->company->id)
            ->where('defaultStatus', 'active')
            ->has('roles')
            ->has('nextUsernamePreview')
        );
});

test('user without the create permission cannot open the employee create page', function (): void {
    $user = User::factory()->create(['company_id' => $this->company->id]);
    $user->assignRole('driver');

    $this->actingAs($user)
        ->get(route('employee-users.create'))
        ->assertForbidden();
});
