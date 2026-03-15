<?php

use App\Models\Gate;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function commuterWithToken(string $token): User
{
    $role = Role::firstOrCreate([
        'name' => 'commuter',
        'guard_name' => 'web',
    ], [
        'type' => 'commuter',
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);
    $user->forceFill([
        'api_token' => hash('sha256', $token),
    ])->save();

    return $user;
}

test('commuter can fetch nearest stops for origin and destination', function () {
    $user = commuterWithToken('token-route-finding');

    $gate = Gate::query()->create([
        'gate_name' => 'Gate A',
        'status' => 'active',
        'bays' => 4,
        'created_by' => $user->id,
    ]);

    $route1 = Route::query()->create([
        'route_name' => 'PITX-MAKATI',
        'gate_id' => $gate->id,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $route2 = Route::query()->create([
        'route_name' => 'PITX-TAFT',
        'gate_id' => $gate->id,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $inactiveRoute = Route::query()->create([
        'route_name' => 'INACTIVE-ROUTE',
        'gate_id' => $gate->id,
        'status' => 'inactive',
        'created_by' => $user->id,
    ]);

    // Route 1 has a stop near origin and another near destination.
    RouteStop::query()->create([
        'route_id' => $route1->id,
        'stop_name' => 'PITX Main Bay',
        'stop_type' => 'origin',
        'latitude' => 14.5096,
        'longitude' => 120.9915,
        'stop_order' => 1,
        'created_by' => $user->id,
    ]);
    RouteStop::query()->create([
        'route_id' => $route1->id,
        'stop_name' => 'Makati Center',
        'stop_type' => 'destination',
        'latitude' => 14.5548,
        'longitude' => 121.0245,
        'stop_order' => 2,
        'created_by' => $user->id,
    ]);

    // Route 2 only has a stop near origin.
    RouteStop::query()->create([
        'route_id' => $route2->id,
        'stop_name' => 'Taft Northbound',
        'stop_type' => 'stop',
        'latitude' => 14.5120,
        'longitude' => 120.9940,
        'stop_order' => 1,
        'created_by' => $user->id,
    ]);

    // Inactive route should not appear.
    RouteStop::query()->create([
        'route_id' => $inactiveRoute->id,
        'stop_name' => 'Inactive Stop',
        'stop_type' => 'stop',
        'latitude' => 14.5100,
        'longitude' => 120.9920,
        'stop_order' => 1,
        'created_by' => $user->id,
    ]);

    $response = $this->withHeader('Authorization', 'Bearer token-route-finding')
        ->getJson('/api/v1/route-finding/nearest-stops?' . http_build_query([
            'origin_lat' => 14.5094,
            'origin_lng' => 120.9912,
            'destination_lat' => 14.5547,
            'destination_lng' => 121.0244,
            'limit' => 3,
        ]));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'origin',
                'destination',
                'origin_nearest_stops',
                'destination_nearest_stops',
                'matching_routes',
            ],
        ])
        ->assertJsonPath('data.origin_nearest_stops.0.route.route_name', 'PITX-MAKATI')
        ->assertJsonPath('data.destination_nearest_stops.0.route.route_name', 'PITX-MAKATI');

    expect(collect($response->json('data.matching_routes'))->pluck('route_name')->all())
        ->toContain('PITX-MAKATI')
        ->not->toContain('INACTIVE-ROUTE');
});
