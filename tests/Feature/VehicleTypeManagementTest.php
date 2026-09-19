<?php

use App\Models\Company;
use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        ['name' => 'admin', 'type' => 'internal'],
        ['name' => 'it', 'type' => 'internal'],
        ['name' => 'terminal manager', 'type' => 'internal'],
        ['name' => 'operator', 'type' => 'external'],
    ] as $role) {
        Role::query()->firstOrCreate([
            'name' => $role['name'],
            'guard_name' => 'web',
        ], ['type' => $role['type']]);
    }

    foreach ([
        'vehicle_types.viewAny',
        'vehicle_types.create',
        'vehicle_types.update',
        'vehicle_types.delete',
        'vehicles.create',
        'vehicles.update',
    ] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

function vehicleTypeManager(): User
{
    $user = User::factory()->internal()->create();
    $user->assignRole('admin');
    $user->givePermissionTo([
        'vehicle_types.viewAny',
        'vehicle_types.create',
        'vehicle_types.update',
        'vehicle_types.delete',
        'vehicles.create',
        'vehicles.update',
    ]);

    return $user;
}

test('an authorized internal user can manage vehicle types', function (): void {
    $user = vehicleTypeManager();

    $this->actingAs($user)
        ->get(route('vehicle-types.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('VehicleType/Index')
            ->where('canDelete', true));

    $this->actingAs($user)
        ->post(route('vehicle-types.store'), ['type_name' => '  Modern   Jeepney  ', 'is_active' => true])
        ->assertRedirect(route('vehicle-types.index'));

    expect(VehicleType::query()->where('type_name', 'Modern Jeepney')->exists())->toBeTrue();
});

test('unauthorized internal users and external users cannot access management routes', function (): void {
    $unauthorizedInternal = User::factory()->internal()->create();
    $unauthorizedInternal->assignRole('admin');
    $externalCompany = Company::factory()->create();
    $externalUser = User::factory()->external($externalCompany->id)->create();
    $externalUser->assignRole('operator');

    $this->actingAs($unauthorizedInternal)
        ->get(route('vehicle-types.index'))
        ->assertForbidden();

    $this->actingAs($externalUser)
        ->get(route('vehicle-types.index'))
        ->assertRedirect(route('registration.status'));
});

test('duplicate names and assigned types cannot be deleted', function (): void {
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['type_name' => 'Bus', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->post(route('vehicle-types.store'), ['type_name' => 'Bus'])
        ->assertSessionHasErrors('type_name');

    $company = Company::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
    $gate = Gate::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
    $route = Route::factory()->create(['gate_id' => $gate->id, 'created_by' => $user->id, 'updated_by' => $user->id]);
    Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'vehicle_type_id' => $vehicleType->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('vehicle-types.destroy', $vehicleType))
        ->assertSessionHasErrors('vehicle_type');

    $this->assertModelExists($vehicleType);
});

test('inactive types cannot be assigned but an existing inactive assignment can be retained', function (): void {
    $user = vehicleTypeManager();
    $inactiveType = VehicleType::factory()->create(['is_active' => false, 'created_by' => $user->id]);
    $company = Company::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
    $gate = Gate::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
    $route = Route::factory()->create(['gate_id' => $gate->id, 'created_by' => $user->id, 'updated_by' => $user->id]);

    $data = [
        'plate_number' => 'ABC1234',
        'body_number' => 'BODY-1234',
        'capacity' => 30,
        'company_id' => $company->id,
        'route_id' => $route->id,
        'vehicle_type_id' => $inactiveType->id,
    ];

    $this->actingAs($user)
        ->post(route('vehicles.store'), $data)
        ->assertSessionHasErrors('vehicle_type_id');

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'vehicle_type_id' => $inactiveType->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->put(route('vehicles.update', $vehicle), [
            ...$data,
            'plate_number' => $vehicle->plate_number,
            'body_number' => $vehicle->body_number,
        ])
        ->assertRedirect(route('vehicles.index'));

    expect($vehicle->fresh()->vehicle_type_id)->toBe($inactiveType->id);
});

test('an authorized user can delete an unassigned vehicle type', function (): void {
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->delete(route('vehicle-types.destroy', $vehicleType))
        ->assertRedirect(route('vehicle-types.index'));

    $this->assertModelMissing($vehicleType);
});
