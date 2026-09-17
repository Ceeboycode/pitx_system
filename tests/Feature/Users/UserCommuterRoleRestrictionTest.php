<?php

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['users.create', 'users.update', 'users.view'] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $adminRole = Role::query()->firstOrCreate(
        ['name' => 'user-commuter-restriction-admin', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $adminRole->givePermissionTo(['users.create', 'users.update', 'users.view']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);

    // Mirrors what AuthTokenController::register() and RoleSeeder both
    // touch: the commuter role is self-registered from the mobile app and
    // must never be offered or accepted from this internal admin module.
    Role::query()->firstOrCreate(
        ['name' => Role::NAME_COMMUTER, 'guard_name' => 'web'],
        ['type' => 'external'],
    );

    Role::query()->firstOrCreate(
        ['name' => 'operator', 'guard_name' => 'web'],
        ['type' => 'external'],
    );
});

test('the create page never offers the commuter role', function (): void {
    $this->actingAs($this->admin)
        ->get(route('users.create'))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Create')
            ->where('roles', fn ($roles) => collect($roles)->doesntContain('name', Role::NAME_COMMUTER)));
});

test('the detail page never offers the commuter role', function (): void {
    $target = User::factory()->create();
    $target->assignRole('operator');

    $this->actingAs($this->admin)
        ->get(route('users.show', $target))
        ->assertInertia(fn ($page) => $page
            ->component('Users/Edit')
            ->where('roles', fn ($roles) => collect($roles)->doesntContain('name', Role::NAME_COMMUTER)));
});

test('creating a user with the commuter role is rejected', function (): void {
    $this->actingAs($this->admin)
        ->post(route('users.store'), [
            'name' => 'Test Commuter',
            'email' => 'test-commuter@example.com',
            'role' => Role::NAME_COMMUTER,
        ])
        ->assertSessionHasErrors('role');

    expect(User::query()->where('email', 'test-commuter@example.com')->exists())->toBeFalse();
});

test('updating a user to the commuter role is rejected', function (): void {
    $target = User::factory()->create();
    $target->assignRole('operator');

    $this->actingAs($this->admin)
        ->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => Role::NAME_COMMUTER,
        ])
        ->assertSessionHasErrors('role');

    expect($target->fresh()->hasRole(Role::NAME_COMMUTER))->toBeFalse();
});
