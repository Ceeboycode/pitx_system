<?php

use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Roles the Gate/Route factories look up when resolving a creator.
    foreach (['admin', 'it', 'terminal manager'] as $name) {
        Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['type' => 'internal']);
    }

    foreach (['gates.view', 'gates.update', 'routes.view', 'routes.update', 'roles.view', 'roles.update'] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

function makeViewOnlyUser(string $viewPermission): User
{
    $role = Role::query()->create([
        'name' => 'viewer-'.str($viewPermission)->slug(),
        'guard_name' => 'web',
        'type' => 'internal',
    ]);
    $role->givePermissionTo($viewPermission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('a gates.view-only user can load the gate edit page but cannot update it', function (): void {
    $viewer = makeViewOnlyUser('gates.view');
    $gate = Gate::factory()->create();

    $this->actingAs($viewer)
        ->get(route('gates.edit', $gate))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Gates/Edit'));

    // A fully valid payload, so the request clears form validation and the
    // 403 we assert on comes from the controller's own authorize() call.
    $this->actingAs($viewer)
        ->put(route('gates.update', $gate), [
            'gate_name' => 'Some Other Gate Name',
            'status' => 'active',
            'bays' => 8,
            'location' => 'Ground Floor',
        ])
        ->assertForbidden();
});

test('a routes.view-only user can load the route edit page but cannot update it', function (): void {
    $viewer = makeViewOnlyUser('routes.view');
    Gate::factory()->create();
    $route = Route::factory()->create();

    $this->actingAs($viewer)
        ->get(route('routes.edit', $route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Route/Edit'));

    $this->actingAs($viewer)
        ->put(route('routes.update', $route), [
            'route_name' => $route->route_name,
            'gate_id' => $route->gate_id,
            'origin_name' => $route->origin_name,
            'origin_lat' => $route->origin_lat,
            'origin_lng' => $route->origin_lng,
            'destination_name' => $route->destination_name,
            'destination_lat' => $route->destination_lat,
            'destination_lng' => $route->destination_lng,
            'stops' => [
                [
                    'stop_name' => 'PITX',
                    'stop_type' => 'origin',
                    'latitude' => 14.5096,
                    'longitude' => 120.9915,
                    'stop_order' => 1,
                ],
                [
                    'stop_name' => $route->destination_name,
                    'stop_type' => 'destination',
                    'latitude' => $route->destination_lat,
                    'longitude' => $route->destination_lng,
                    'stop_order' => 2,
                ],
            ],
        ])
        ->assertForbidden();
});

test('a roles.view-only user can load the role edit page but cannot update it', function (): void {
    $viewer = makeViewOnlyUser('roles.view');
    $role = Role::query()->create(['name' => 'target-role', 'guard_name' => 'web', 'type' => 'internal']);

    $this->actingAs($viewer)
        ->get(route('roles.edit', $role))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Roles/Edit'));

    $this->actingAs($viewer)
        ->put(route('roles.update', $role), [
            'name' => 'target-role',
            'type' => 'internal',
        ])
        ->assertForbidden();
});
