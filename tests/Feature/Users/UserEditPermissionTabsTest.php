<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
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

dataset('seeded role tab access', [
    'operator' => ['operator', ['vehicles' => true, 'dispatches' => true, 'employees' => true]],
    'dispatcher' => ['dispatcher', ['vehicles' => false, 'dispatches' => true, 'employees' => false]],
    'driver' => ['driver', ['vehicles' => true, 'dispatches' => true, 'employees' => false]],
    'commuter' => ['commuter', ['vehicles' => false, 'dispatches' => false, 'employees' => false]],
    'admin' => ['admin', ['vehicles' => false, 'dispatches' => false, 'employees' => false]],
    'terminal manager' => ['terminal manager', ['vehicles' => false, 'dispatches' => false, 'employees' => false]],
    'super-admin' => ['super-admin', ['vehicles' => false, 'dispatches' => false, 'employees' => false]],
]);

it('exposes the permission-gated tabs each seeded role should see', function (string $roleName, array $expected): void {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);

    $target = User::factory()->create();
    $target->assignRole($roleName);

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('tabAccess', $expected));
})->with('seeded role tab access');

test('a custom role gets a tab as soon as it holds any permission in that tab\'s group', function (): void {
    foreach (['external_dispatches.create', 'external_vehicles.view', 'external_users.viewAny'] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $customRole = Role::query()->create(['name' => 'custom-dispatch-clerk', 'guard_name' => 'web', 'type' => 'external']);
    $customRole->givePermissionTo('external_dispatches.create');

    $target = User::factory()->create();
    $target->assignRole($customRole);

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page
            ->where('tabAccess', ['vehicles' => false, 'dispatches' => true, 'employees' => false]));
});

test('editing a role\'s permissions changes its users\' tabs on the next visit', function (): void {
    Permission::query()->firstOrCreate(['name' => 'external_vehicles.view', 'guard_name' => 'web']);

    $customRole = Role::query()->create(['name' => 'custom-fleet-viewer', 'guard_name' => 'web', 'type' => 'external']);

    $target = User::factory()->create();
    $target->assignRole($customRole);

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page->where('tabAccess.vehicles', false));

    $customRole->givePermissionTo('external_vehicles.view');

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page->where('tabAccess.vehicles', true));

    $customRole->syncPermissions([]);

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page->where('tabAccess.vehicles', false));
});

test('only the driver role itself gets driver tabs without permissions', function (): void {
    $lookalike = Role::query()->create(['name' => 'driver-trainee', 'guard_name' => 'web', 'type' => 'external']);

    $target = User::factory()->create();
    $target->assignRole($lookalike);

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page
            ->where('tabAccess', ['vehicles' => false, 'dispatches' => false, 'employees' => false]));
});

test('no permission-gated tabs are exposed when the edited user has no role at all', function (): void {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('tabAccess', ['vehicles' => false, 'dispatches' => false, 'employees' => false]));
});

test('edit page exposes the edited user\'s email verification timestamp', function (): void {
    $verified = User::factory()->create(['email_verified_at' => now()]);
    $unverified = User::factory()->unverified()->create();

    $this->actingAs($this->admin)
        ->get(route('users.show', $verified))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('user.email_verified_at', fn ($value) => $value !== null));

    $this->actingAs($this->admin)
        ->get(route('users.show', $unverified))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('user.email_verified_at', null));
});
