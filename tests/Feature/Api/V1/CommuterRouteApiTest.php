<?php

use App\Models\Gate;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public location search returns active route locations', function () {
    $creator = User::factory()->create();
    $gate = Gate::factory()->create([
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    $route = Route::factory()->create([
        'gate_id' => $gate->id,
        'route_name' => 'PITX to SM Mall of Asia',
        'origin_name' => 'PITX',
        'destination_name' => 'SM Mall of Asia',
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    RouteStop::factory()->create([
        'route_id' => $route->id,
        'stop_name' => 'Ayala Malls Manila Bay',
        'address' => 'Diosdado Macapagal Blvd, Paranaque',
        'stop_order' => 1,
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    $response = $this->getJson('/api/v1/locations?search=mall');

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Ayala Malls Manila Bay');
});

test('public route search returns direct matching active routes in forward order', function () {
    $creator = User::factory()->create();
    $gate = Gate::factory()->create([
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    $route = Route::factory()->create([
        'gate_id' => $gate->id,
        'route_name' => 'PITX to SM Mall of Asia',
        'origin_name' => 'PITX',
        'destination_name' => 'SM Mall of Asia',
        'distance_meters' => 6400,
        'duration_seconds' => 1200,
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    RouteStop::factory()->create([
        'route_id' => $route->id,
        'stop_name' => 'Ayala Malls Manila Bay',
        'address' => 'Diosdado Macapagal Blvd, Paranaque',
        'stop_order' => 1,
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    $response = $this->getJson('/api/v1/routes/search?origin=pitx&destination=mall%20of%20asia');

    $response->assertOk()
        ->assertJsonPath('meta.count', 1)
        ->assertJsonPath('data.0.route_name', 'PITX to SM Mall of Asia')
        ->assertJsonPath('data.0.gate.gate_name', $gate->gate_name)
        ->assertJsonPath('data.0.match.origin_label', 'PITX')
        ->assertJsonPath('data.0.match.destination_label', 'SM Mall of Asia');
});

test('public route search excludes reversed travel direction', function () {
    $creator = User::factory()->create();
    $gate = Gate::factory()->create([
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    $route = Route::factory()->create([
        'gate_id' => $gate->id,
        'route_name' => 'PITX to Cavite',
        'origin_name' => 'PITX',
        'destination_name' => 'Cavite',
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    RouteStop::factory()->create([
        'route_id' => $route->id,
        'stop_name' => 'Baclaran',
        'address' => 'Baclaran',
        'stop_order' => 1,
        'created_by' => $creator->id,
        'updated_by' => $creator->id,
    ]);

    $response = $this->getJson('/api/v1/routes/search?origin=cavite&destination=pitx');

    $response->assertOk()
        ->assertJsonPath('meta.count', 0)
        ->assertJsonCount(0, 'data');
});
