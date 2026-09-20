<?php

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        ['name' => 'operator', 'type' => 'external'],
        ['name' => 'admin', 'type' => 'internal'],
        ['name' => 'super-admin', 'type' => 'internal'],
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

    foreach ([
        'vehicles.viewAny',
        'vehicles.view',
        'vehicles.update',
        'vehicles.archive',
        'vehicles.toggleStatus',
    ] as $perm) {
        Permission::query()->firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    $this->internalRole = Role::query()->where('name', 'admin')->first();

    $this->internalRole->syncPermissions([
        'vehicles.viewAny',
        'vehicles.view',
        'vehicles.update',
        'vehicles.archive',
        'vehicles.toggleStatus',
    ]);
});

function createInternalAdmin(): User
{
    $role = Role::query()->where('name', 'admin')->first();
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('internal user can filter vehicles by vehicle_type with varying case or format', function (): void {
    $admin = createInternalAdmin();
    $company = Company::factory()->verified()->create();

    $bus = Vehicle::factory()->create([
        'company_id' => $company->id,
        'vehicle_type_id' => \App\Models\VehicleType::factory()->create(['type_name' => 'Bus'])->id,
        'plate_number' => 'BUS101',
    ]);

    $miniBus = Vehicle::factory()->create([
        'company_id' => $company->id,
        'vehicle_type_id' => \App\Models\VehicleType::factory()->create(['type_name' => 'Mini Bus'])->id,
        'plate_number' => 'MINI102',
    ]);

    $uvExpress = Vehicle::factory()->create([
        'company_id' => $company->id,
        'vehicle_type_id' => \App\Models\VehicleType::factory()->create(['type_name' => 'UV Express'])->id,
        'plate_number' => 'UV103',
    ]);

    // Test filter with 'Mini Bus' id
    $this->actingAs($admin)
        ->get(route('vehicles.index', ['vehicle_type' => $miniBus->vehicle_type_id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_number', 'MINI102')
        );

    // Test filter with 'UV Express' id
    $this->actingAs($admin)
        ->get(route('vehicles.index', ['vehicle_type' => $uvExpress->vehicle_type_id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_number', 'UV103')
        );
});

test('internal user can filter vehicles by operational status and verification status', function (): void {
    $admin = createInternalAdmin();
    $company = Company::factory()->verified()->create();

    $activeVerified = Vehicle::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'verification_status' => 'verified',
        'plate_number' => 'ACT001',
    ]);

    $inactiveForVerification = Vehicle::factory()->create([
        'company_id' => $company->id,
        'status' => 'inactive',
        'verification_status' => 'for_verification',
        'plate_number' => 'INA002',
    ]);

    $suspendedVerified = Vehicle::factory()->create([
        'company_id' => $company->id,
        'status' => 'suspended',
        'verification_status' => 'verified',
        'plate_number' => 'SUS003',
    ]);

    // Filter by operational status 'suspended'
    $this->actingAs($admin)
        ->get(route('vehicles.index', ['status' => 'suspended']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_number', 'SUS003')
        );

});

test('internal user can search vehicles by plate number or body number', function (): void {
    $admin = createInternalAdmin();
    $company = Company::factory()->verified()->create();

    Vehicle::factory()->create([
        'company_id' => $company->id,
        'plate_number' => 'XYZ9999',
        'body_number' => 'BODY-01',
    ]);

    Vehicle::factory()->create([
        'company_id' => $company->id,
        'plate_number' => 'ABC1234',
        'body_number' => 'BODY-99',
    ]);

    $this->actingAs($admin)
        ->get(route('vehicles.index', ['search' => 'XYZ9999']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_number', 'XYZ9999')
        );

    $this->actingAs($admin)
        ->get(route('vehicles.index', ['search' => 'BODY-99']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_number', 'ABC1234')
        );
});
