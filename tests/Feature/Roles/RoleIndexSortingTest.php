<?php

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::query()->firstOrCreate(['name' => 'roles.viewAny', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'sample.permission', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'sample.permission.two', 'guard_name' => 'web']);

    $viewerRole = Role::query()->firstOrCreate(
        ['name' => 'role-index-sorting-viewer', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $viewerRole->givePermissionTo('roles.viewAny');

    $this->viewer = User::factory()->create();
    $this->viewer->assignRole($viewerRole);
});

test('index sorts by name descending when requested', function (): void {
    Role::query()->firstOrCreate(['name' => 'alpha-role', 'guard_name' => 'web'], ['type' => 'internal']);
    Role::query()->firstOrCreate(['name' => 'zulu-role', 'guard_name' => 'web'], ['type' => 'internal']);

    $this->actingAs($this->viewer)
        ->get(route('roles.index', ['sort_by' => 'name', 'sort_dir' => 'desc']))
        ->assertInertia(fn ($page) => $page
            ->component('Roles/Index')
            ->where('roles.data.0.name', 'zulu-role'));
});

test('index sorts by type', function (): void {
    Role::query()->firstOrCreate(['name' => 'internal-role', 'guard_name' => 'web'], ['type' => 'internal']);
    Role::query()->firstOrCreate(['name' => 'external-role', 'guard_name' => 'web'], ['type' => 'external']);

    $this->actingAs($this->viewer)
        ->get(route('roles.index', ['sort_by' => 'type', 'sort_dir' => 'asc']))
        ->assertInertia(fn ($page) => $page
            ->component('Roles/Index')
            ->where('roles.data.0.type', 'external'));
});

test('index sorts by permissions count', function (): void {
    $fewPermissions = Role::query()->create(['name' => 'few-permissions-role', 'guard_name' => 'web', 'type' => 'internal']);
    $fewPermissions->givePermissionTo('sample.permission');

    $manyPermissions = Role::query()->create(['name' => 'many-permissions-role', 'guard_name' => 'web', 'type' => 'internal']);
    $manyPermissions->givePermissionTo(['sample.permission', 'sample.permission.two']);

    $this->actingAs($this->viewer)
        ->get(route('roles.index', ['sort_by' => 'permissions_count', 'sort_dir' => 'desc']))
        ->assertInertia(fn ($page) => $page
            ->component('Roles/Index')
            ->where('roles.data.0.name', 'many-permissions-role'));
});
