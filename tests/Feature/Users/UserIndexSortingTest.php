<?php

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::query()->firstOrCreate(['name' => 'users.viewAny', 'guard_name' => 'web']);

    $viewerRole = Role::query()->firstOrCreate(
        ['name' => 'user-index-sorting-viewer', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $viewerRole->givePermissionTo('users.viewAny');

    $this->viewer = User::factory()->create(['name' => 'Viewer Admin']);
    $this->viewer->assignRole($viewerRole);
});

test('index sorts by name descending when requested', function (): void {
    User::factory()->create(['name' => 'Alice Anderson']);
    User::factory()->create(['name' => 'Zed Zimmerman']);

    $response = $this->actingAs($this->viewer)
        ->get(route('users.index', ['sort_by' => 'name', 'sort_dir' => 'desc']));

    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->where('users.data.0.name', 'Zed Zimmerman'));
});

test('index sorts by status', function (): void {
    User::factory()->create(['name' => 'Active User Alpha', 'status' => 'active']);
    User::factory()->create(['name' => 'Inactive User Beta', 'status' => 'inactive']);

    $response = $this->actingAs($this->viewer)
        ->get(route('users.index', ['sort_by' => 'status', 'sort_dir' => 'asc']));

    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->where('users.data.0.status', 'active'));
});

test('index still defaults to ordering by name when sort_by is invalid', function (): void {
    User::factory()->create(['name' => 'Bravo User']);
    User::factory()->create(['name' => 'Alpha User']);

    $response = $this->actingAs($this->viewer)
        ->get(route('users.index', ['sort_by' => 'not-a-real-column']));

    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->where('users.data.0.name', 'Alpha User'));
});
