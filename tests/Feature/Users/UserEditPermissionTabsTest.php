<?php

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Permission the acting admin needs just to open someone else's edit page.
    Permission::query()->firstOrCreate(['name' => 'users.update', 'guard_name' => 'web']);

    $adminRole = Role::query()->firstOrCreate(
        ['name' => 'user-edit-tabs-admin', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $adminRole->givePermissionTo('users.update');

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);
});

/** Every permission name in the "external_users" group (mirrors PermissionSeeder). */
function editTabsExternalUserPermissionNames(): array
{
    return [
        'external_users.viewAny',
        'external_users.view',
        'external_users.create',
        'external_users.update',
        'external_users.archive',
        'external_users.toggleStatus',
        'external_users.resetPassword',
    ];
}

/** Every permission name in the "external_dispatches" group (mirrors PermissionSeeder). */
function editTabsExternalDispatchPermissionNames(): array
{
    return [
        'external_dispatches.viewAny',
        'external_dispatches.view',
        'external_dispatches.create',
        'external_dispatches.update',
        'external_dispatches.depart',
        'external_dispatches.requestChange',
    ];
}

test('employees and dispatches tabs are exposed when the edited user role has every permission in each group', function (): void {
    foreach ([...editTabsExternalUserPermissionNames(), ...editTabsExternalDispatchPermissionNames()] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $fullRole = Role::query()->create(['name' => 'full-external', 'guard_name' => 'web', 'type' => 'external']);
    $fullRole->givePermissionTo([
        ...editTabsExternalUserPermissionNames(),
        ...editTabsExternalDispatchPermissionNames(),
    ]);

    $target = User::factory()->create();
    $target->assignRole($fullRole);

    $this->actingAs($this->admin)
        ->get(route('users.edit', $target))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('canManageExternalUsers', true)
            ->where('canManageExternalDispatches', true));
});

test('employees and dispatches tabs are hidden when the edited user role is missing even one permission in the group', function (): void {
    foreach ([...editTabsExternalUserPermissionNames(), ...editTabsExternalDispatchPermissionNames()] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $partialRole = Role::query()->create(['name' => 'partial-external', 'guard_name' => 'web', 'type' => 'external']);

    // Every external_users permission except "resetPassword", and only
    // two of the six external_dispatches permissions.
    $partialRole->givePermissionTo([
        'external_users.viewAny',
        'external_users.view',
        'external_users.create',
        'external_users.update',
        'external_users.archive',
        'external_users.toggleStatus',
        'external_dispatches.viewAny',
        'external_dispatches.view',
    ]);

    $target = User::factory()->create();
    $target->assignRole($partialRole);

    $this->actingAs($this->admin)
        ->get(route('users.edit', $target))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('canManageExternalUsers', false)
            ->where('canManageExternalDispatches', false));
});

test('employees and dispatches tabs are hidden when the edited user has no role at all', function (): void {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('users.edit', $target))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('canManageExternalUsers', false)
            ->where('canManageExternalDispatches', false));
});
