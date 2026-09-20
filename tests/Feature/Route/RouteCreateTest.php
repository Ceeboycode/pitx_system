<?php

use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::query()->firstOrCreate(['name' => 'routes.create', 'guard_name' => 'web']);

    // Roles the Route/Gate factories look up when resolving a creator.
    foreach (['admin', 'it', 'terminal manager'] as $name) {
        Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['type' => 'internal']);
    }

    $role = Role::query()->firstOrCreate(
        ['name' => 'route-creator', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $role->givePermissionTo('routes.create');

    $this->creator = User::factory()->create();
    $this->creator->assignRole($role);
});

function routeStorePayload(Gate $gate, array $overrides = []): array
{
    return array_merge([
        'route_name' => 'PITX - Cavite',
        'gate_id' => $gate->id,
        'origin_name' => 'PITX',
        'origin_lat' => 14.5096,
        'origin_lng' => 120.9915,
        'destination_name' => 'Cavite City',
        'destination_lat' => 14.4791,
        'destination_lng' => 120.8970,
        'distance_meters' => 15200,
        'duration_seconds' => 1800,
        'route_geometry' => '{"type":"LineString","coordinates":[[120.99,14.51],[120.89,14.47]]}',
        'stops' => [
            [
                'stop_name' => 'PITX',
                'stop_type' => 'origin',
                'latitude' => 14.5096,
                'longitude' => 120.9915,
                'stop_order' => 1,
            ],
            [
                'stop_name' => 'Cavite City',
                'stop_type' => 'destination',
                'latitude' => 14.4791,
                'longitude' => 120.8970,
                'stop_order' => 2,
            ],
        ],
    ], $overrides);
}

test('the create page renders the gates and the map config', function (): void {
    $gate = Gate::factory()->create();

    $this->actingAs($this->creator)
        ->get(route('routes.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Route/Create')
            ->where('gates.0.id', $gate->id)
            ->where('mapConfig.pitx.name', 'PITX')
        );
});

test('users without routes.create cannot open the create page or store a route', function (): void {
    $viewerRole = Role::query()->create(['name' => 'route-viewer', 'guard_name' => 'web', 'type' => 'internal']);

    $viewer = User::factory()->create();
    $viewer->assignRole($viewerRole);

    $this->actingAs($viewer)
        ->get(route('routes.create'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('routes.store'), routeStorePayload(Gate::factory()->create()))
        ->assertForbidden();

    expect(Route::query()->count())->toBe(0);
});

test('a route with a custom name is created with its stops', function (): void {
    $gate = Gate::factory()->create();

    $this->actingAs($this->creator)
        ->post(route('routes.store'), routeStorePayload($gate))
        ->assertRedirect(route('routes.index'));

    $route = Route::query()->where('route_name', 'PITX - Cavite')->firstOrFail();

    expect($route->gate_id)->toBe($gate->id)
        ->and($route->stops)->toHaveCount(2);
});

test('a route name that is still just the PITX prefix is rejected', function (string $name): void {
    $gate = Gate::factory()->create();

    $this->actingAs($this->creator)
        ->post(route('routes.store'), routeStorePayload($gate, ['route_name' => $name]))
        ->assertSessionHasErrors(['route_name' => 'Enter a route name after the "PITX - " prefix.']);

    expect(Route::query()->count())->toBe(0);
})->with([
    'the untouched prefix' => ['PITX - '],
    'the prefix without its trailing space' => ['PITX -'],
    'just PITX' => ['PITX'],
]);
