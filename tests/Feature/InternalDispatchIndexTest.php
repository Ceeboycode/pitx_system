<?php

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\Gate as GateModel;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::query()->firstOrCreate(['name' => 'dispatches.viewAny', 'guard_name' => 'web']);

    foreach ([
        ['name' => 'operator', 'type' => 'external'],
        ['name' => 'admin', 'type' => 'internal'],
        ['name' => 'it', 'type' => 'internal'],
        ['name' => 'terminal manager', 'type' => 'internal'],
        ['name' => 'dispatcher', 'type' => 'external'],
        ['name' => 'driver', 'type' => 'external'],
    ] as $role) {
        Role::query()->firstOrCreate([
            'name' => $role['name'],
            'guard_name' => 'web',
        ], [
            'type' => $role['type'],
        ]);
    }

    $viewerRole = Role::query()->firstOrCreate(
        ['name' => 'dispatch-index-viewer', 'guard_name' => 'web'],
        ['type' => 'internal'],
    );
    $viewerRole->givePermissionTo('dispatches.viewAny');

    $this->viewer = User::factory()->create();
    $this->viewer->assignRole($viewerRole);
    $this->viewer->assignRole('admin');

    $this->gate = GateModel::factory()->create();
});

function makeDispatchFor(Company $company, Route $route, string $plateNumber, string $status): Dispatch
{
    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'plate_number' => $plateNumber,
    ]);

    return Dispatch::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'gate_id' => $route->gate_id,
        'plate_number' => $plateNumber,
        'status' => $status,
    ]);
}

test('index lists one row per dispatch instead of one row per company', function (): void {
    $companyA = Company::factory()->create(['company_name' => 'Northstar Provincial Transit']);
    $companyB = Company::factory()->create(['company_name' => 'Southbay Commuter Transport']);
    $routeA = Route::factory()->create(['gate_id' => $this->gate->id]);
    $routeB = Route::factory()->create(['gate_id' => $this->gate->id]);

    // Two dispatches for the same company should yield two rows, not one
    // company row with a count - that's exactly the bug being fixed.
    makeDispatchFor($companyA, $routeA, 'NOR0001', 'pending');
    makeDispatchFor($companyA, $routeA, 'NOR0002', 'arrived');
    makeDispatchFor($companyB, $routeB, 'SOU0001', 'departed');

    $this->actingAs($this->viewer)
        ->get(route('dispatches.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Dispatches/Index')
            ->has('dispatches.data', 3)
            ->has('dispatches.data.0.company.company_name')
            ->has('dispatches.data.0.vehicle.plate_number'));
});

test('search matches a dispatch by company name', function (): void {
    $companyA = Company::factory()->create(['company_name' => 'Northstar Provincial Transit']);
    $companyB = Company::factory()->create(['company_name' => 'Southbay Commuter Transport']);
    $routeA = Route::factory()->create(['gate_id' => $this->gate->id]);
    $routeB = Route::factory()->create(['gate_id' => $this->gate->id]);

    makeDispatchFor($companyA, $routeA, 'NOR0001', 'pending');
    makeDispatchFor($companyB, $routeB, 'SOU0001', 'pending');

    $this->actingAs($this->viewer)
        ->get(route('dispatches.index', ['search' => 'Northstar']))
        ->assertInertia(fn ($page) => $page
            ->component('Dispatches/Index')
            ->has('dispatches.data', 1)
            ->where('dispatches.data.0.company.company_name', 'Northstar Provincial Transit'));
});

test('search matches a dispatch by plate number', function (): void {
    $company = Company::factory()->create();
    $route = Route::factory()->create(['gate_id' => $this->gate->id]);

    makeDispatchFor($company, $route, 'ABC1234', 'pending');
    makeDispatchFor($company, $route, 'XYZ9999', 'pending');

    $this->actingAs($this->viewer)
        ->get(route('dispatches.index', ['search' => 'ABC1234']))
        ->assertInertia(fn ($page) => $page
            ->has('dispatches.data', 1)
            ->where('dispatches.data.0.vehicle.plate_number', 'ABC1234'));
});

test('status filter narrows results to the selected dispatch status', function (): void {
    $company = Company::factory()->create();
    $route = Route::factory()->create(['gate_id' => $this->gate->id]);

    makeDispatchFor($company, $route, 'ABC1111', 'pending');
    makeDispatchFor($company, $route, 'ABC2222', 'departed');

    $this->actingAs($this->viewer)
        ->get(route('dispatches.index', ['status' => 'departed']))
        ->assertInertia(fn ($page) => $page
            ->has('dispatches.data', 1)
            ->where('dispatches.data.0.status', 'departed'));
});

test('index shows the vehicle type name and search matches it', function (): void {
    $company = Company::factory()->create();
    $route = Route::factory()->create(['gate_id' => $this->gate->id]);
    $vehicleType = VehicleType::factory()->create(['type_name' => 'Provincial Bus']);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'vehicle_type_id' => $vehicleType->id,
        'plate_number' => 'TYP0001',
    ]);
    Dispatch::factory()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'gate_id' => $route->gate_id,
        'plate_number' => 'TYP0001',
        'status' => 'arrived',
    ]);
    makeDispatchFor($company, $route, 'OTH0001', 'arrived');

    $this->actingAs($this->viewer)
        ->get(route('dispatches.index', ['search' => 'Provincial Bus']))
        ->assertInertia(fn ($page) => $page
            ->has('dispatches.data', 1)
            ->where('dispatches.data.0.vehicle.vehicle_type', 'Provincial Bus'));
});
