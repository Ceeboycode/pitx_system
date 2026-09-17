<?php

use App\Models\Company;
use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function (): void {
    foreach ([
        ['name' => 'operator', 'type' => 'external'],
        ['name' => 'admin', 'type' => 'internal'],
        ['name' => 'it', 'type' => 'internal'],
        ['name' => 'terminal manager', 'type' => 'internal'],
    ] as $role) {
        Role::query()->firstOrCreate([
            'name' => $role['name'],
            'guard_name' => 'web',
        ], [
            'type' => $role['type'],
        ]);
    }

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Gate::factory()->create();
    Company::factory()->create();
    Route::factory()->create();
});

test('search scope matches by company name', function (): void {
    $company = Company::factory()->create(['company_name' => 'Northstar Provincial Transit Corporation']);
    $otherCompany = Company::factory()->create(['company_name' => 'Southbay Commuter Transport']);

    $match = Vehicle::factory()->create(['company_id' => $company->id]);
    $other = Vehicle::factory()->create(['company_id' => $otherCompany->id]);

    $results = Vehicle::query()->search('Northstar')->pluck('id');

    expect($results)->toContain($match->id)
        ->and($results)->not->toContain($other->id);
});

test('search scope matches by route name', function (): void {
    $route = Route::factory()->create(['route_name' => 'PITX - Tagaytay-Mendez']);
    $otherRoute = Route::factory()->create(['route_name' => 'PITX - Bicol']);

    $match = Vehicle::factory()->create(['route_id' => $route->id]);
    $other = Vehicle::factory()->create(['route_id' => $otherRoute->id]);

    $results = Vehicle::query()->search('Tagaytay')->pluck('id');

    expect($results)->toContain($match->id)
        ->and($results)->not->toContain($other->id);
});

test('search scope matches by capacity', function (): void {
    $match = Vehicle::factory()->create(['capacity' => 47]);
    $other = Vehicle::factory()->create(['capacity' => 30]);

    $results = Vehicle::query()->search('47')->pluck('id');

    expect($results)->toContain($match->id)
        ->and($results)->not->toContain($other->id);
});
