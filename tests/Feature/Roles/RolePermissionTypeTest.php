<?php

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['roles.create', 'roles.update', 'roles.viewAny'] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    $adminRole = Role::query()->firstOrCreate(
        ['name' => 'role-type-admin', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $adminRole->givePermissionTo(['roles.create', 'roles.update', 'roles.viewAny']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);

    $this->internalPermission = Permission::query()->firstOrCreate(['name' => 'gates.view', 'guard_name' => 'web']);
    $this->externalPermission = Permission::query()->firstOrCreate(['name' => 'external_vehicles.view', 'guard_name' => 'web']);
});

test('an internal role only keeps internal permissions when it is created', function (): void {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), [
            'name' => 'Gate Watcher',
            'type' => 'internal',
            'permissions' => [$this->internalPermission->id, $this->externalPermission->id],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::query()->where('name', 'Gate Watcher')->firstOrFail();

    expect($role->permissions->pluck('name')->all())->toBe(['gates.view']);
});

test('an external role only keeps external permissions when it is created', function (): void {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), [
            'name' => 'Fleet Viewer',
            'type' => 'external',
            'permissions' => [$this->internalPermission->id, $this->externalPermission->id],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::query()->where('name', 'Fleet Viewer')->firstOrFail();

    expect($role->permissions->pluck('name')->all())->toBe(['external_vehicles.view']);
});

test('changing a role to another type discards the permissions of the old type', function (): void {
    $role = Role::query()->create(['name' => 'Switcher', 'type' => 'internal', 'guard_name' => 'web']);
    $role->syncPermissions([$this->internalPermission->id]);

    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), [
            'name' => 'Switcher',
            'type' => 'external',
            'permissions' => [$this->internalPermission->id, $this->externalPermission->id],
        ])
        ->assertRedirect();

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['external_vehicles.view']);
});

test('saving a role without changing its type cleans out permissions of the other type', function (): void {
    $role = Role::query()->create(['name' => 'Mixed', 'type' => 'internal', 'guard_name' => 'web']);
    $role->syncPermissions([$this->internalPermission->id, $this->externalPermission->id]);

    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), [
            'name' => 'Mixed',
            'type' => 'internal',
            'permissions' => [$this->internalPermission->id, $this->externalPermission->id],
        ])
        ->assertRedirect();

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['gates.view']);
});
