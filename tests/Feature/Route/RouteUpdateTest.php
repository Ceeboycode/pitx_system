<?php

use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::query()->firstOrCreate(['name' => 'routes.update', 'guard_name' => 'web']);

    // Roles the Route/Gate factories look up when resolving a creator.
    foreach (['admin', 'it', 'terminal manager'] as $name) {
        Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['type' => 'internal']);
    }

    $role = Role::query()->firstOrCreate(
        ['name' => 'route-editor', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $role->givePermissionTo('routes.update');

    $this->editor = User::factory()->create();
    $this->editor->assignRole($role);
});

function routeUpdatePayload(Route $route, array $overrides = []): array
{
    return array_merge([
        'route_name' => $route->route_name,
        'gate_id' => $route->gate_id,
        'origin_name' => $route->origin_name,
        'origin_lat' => $route->origin_lat,
        'origin_lng' => $route->origin_lng,
        'destination_name' => $route->destination_name,
        'destination_lat' => $route->destination_lat,
        'destination_lng' => $route->destination_lng,
        'distance_meters' => null,
        'duration_seconds' => null,
        'route_geometry' => null,
        'stops' => [
            [
                'stop_name' => 'PITX',
                'stop_type' => 'origin',
                'latitude' => 14.5096,
                'longitude' => 120.9915,
                'stop_order' => 1,
            ],
            [
                'stop_name' => 'New Destination',
                'stop_type' => 'destination',
                'latitude' => 14.2814,
                'longitude' => 120.8585,
                'stop_order' => 2,
            ],
        ],
    ], $overrides);
}

test('editing only name, gate and stops keeps the saved distance and duration', function (): void {
    $originalGate = Gate::factory()->create();
    $newGate = Gate::factory()->create();

    $route = Route::factory()->create([
        'gate_id' => $originalGate->id,
        'distance_meters' => 28500,
        'duration_seconds' => 3000,
        'route_geometry' => '{"type":"LineString","coordinates":[[120.99,14.51],[120.86,14.28]]}',
    ]);

    $response = $this->actingAs($this->editor)->put(
        route('routes.update', $route),
        routeUpdatePayload($route, [
            'route_name' => 'Renamed Route',
            'gate_id' => $newGate->id,
        ]),
    );

    $response->assertRedirect(route('routes.index'));

    $route->refresh();

    expect($route->route_name)->toBe('Renamed Route')
        ->and($route->gate_id)->toBe($newGate->id)
        ->and((int) $route->distance_meters)->toBe(28500)
        ->and((int) $route->duration_seconds)->toBe(3000)
        ->and($route->route_geometry)->toBe('{"type":"LineString","coordinates":[[120.99,14.51],[120.86,14.28]]}')
        ->and($route->stops)->toHaveCount(2);
});

test('submitting a freshly computed geometry overwrites the saved distance and duration', function (): void {
    $route = Route::factory()->create([
        'gate_id' => Gate::factory()->create()->id,
        'distance_meters' => 28500,
        'duration_seconds' => 3000,
        'route_geometry' => '{"type":"LineString","coordinates":[[120.99,14.51]]}',
    ]);

    $this->actingAs($this->editor)->put(
        route('routes.update', $route),
        routeUpdatePayload($route, [
            'distance_meters' => 41200,
            'duration_seconds' => 4500,
            'route_geometry' => '{"type":"LineString","coordinates":[[120.99,14.51],[120.80,14.20]]}',
        ]),
    )->assertRedirect(route('routes.index'));

    $route->refresh();

    expect((int) $route->distance_meters)->toBe(41200)
        ->and((int) $route->duration_seconds)->toBe(4500)
        ->and($route->route_geometry)->toBe('{"type":"LineString","coordinates":[[120.99,14.51],[120.80,14.20]]}');
});
