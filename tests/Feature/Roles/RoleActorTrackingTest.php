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
        ['name' => 'role-admin', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $adminRole->givePermissionTo(['roles.create', 'roles.update', 'roles.viewAny']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);
});

test('creating a role records the acting user as creator and updater', function (): void {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), [
            'name' => 'Warehouse Staff',
            'type' => 'internal',
            'permissions' => [],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::query()->where('name', 'Warehouse Staff')->firstOrFail();

    expect($role->created_by)->toBe($this->admin->id)
        ->and($role->updated_by)->toBe($this->admin->id);
});

test('updating a role records the acting user as updater but keeps the original creator', function (): void {
    $other = User::factory()->create();

    $role = Role::query()->create([
        'name' => 'Dispatcher',
        'type' => 'internal',
        'guard_name' => 'web',
        'created_by' => $other->id,
        'updated_by' => $other->id,
    ]);

    $this->actingAs($this->admin)->put(route('roles.update', $role), [
        'name' => 'Dispatcher',
        'type' => 'internal',
        'permissions' => [],
    ]);

    $role->refresh();

    expect($role->updated_by)->toBe($this->admin->id)
        ->and($role->created_by)->toBe($other->id);
});

test('the role edit page exposes the creator, updater and human timestamps', function (): void {
    $role = Role::query()->create([
        'name' => 'Auditor',
        'type' => 'internal',
        'guard_name' => 'web',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('roles.edit', $role))
        ->assertInertia(fn ($page) => $page
            ->component('Roles/Edit')
            ->where('role.creator.name', $this->admin->name)
            ->where('role.updater.name', $this->admin->name)
            ->whereNotNull('role.created_at_human')
            ->whereNotNull('role.updated_at_human'),
        );
});
