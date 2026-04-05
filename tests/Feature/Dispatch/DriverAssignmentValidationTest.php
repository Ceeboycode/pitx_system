<?php

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\Gate;
use App\Models\Role;
use App\Models\Route as RouteModel;
use App\Models\User;
use App\Models\Vehicle;

function makeDispatchContext(): array
{
    Role::query()->updateOrCreate(
        ['name' => 'dispatcher', 'guard_name' => 'web'],
        ['type' => 'external'],
    );

    Role::query()->updateOrCreate(
        ['name' => 'driver', 'guard_name' => 'web'],
        ['type' => 'external'],
    );

    $seedUser = User::factory()->create([
        'company_id' => null,
        'must_change_password' => false,
        'status' => 'active',
    ]);

    $company = Company::factory()->verified()->create([
        'created_by' => $seedUser->id,
        'updated_by' => $seedUser->id,
    ]);

    $dispatcher = User::factory()->external($company->id)->create([
        'must_change_password' => false,
        'status' => 'active',
    ]);
    $dispatcher->assignRole('dispatcher');

    $driverA = User::factory()->external($company->id)->create([
        'status' => 'active',
    ]);
    $driverA->assignRole('driver');

    $driverB = User::factory()->external($company->id)->create([
        'status' => 'active',
    ]);
    $driverB->assignRole('driver');

    $gate = Gate::factory()->create([
        'status' => 'active',
        'bays' => 10,
        'created_by' => $seedUser->id,
        'updated_by' => $seedUser->id,
    ]);

    $route = RouteModel::factory()->create([
        'gate_id' => $gate->id,
        'status' => 'active',
        'created_by' => $seedUser->id,
        'updated_by' => $seedUser->id,
    ]);

    $vehicleOne = Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'status' => 'active',
        'created_by' => $seedUser->id,
        'updated_by' => $seedUser->id,
    ]);

    $vehicleTwo = Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'status' => 'active',
        'created_by' => $seedUser->id,
        'updated_by' => $seedUser->id,
    ]);

    return [
        'company' => $company,
        'dispatcher' => $dispatcher,
        'driverA' => $driverA,
        'driverB' => $driverB,
        'gate' => $gate,
        'vehicleOne' => $vehicleOne,
        'vehicleTwo' => $vehicleTwo,
    ];
}

function createDispatch(
    Company $company,
    Vehicle $vehicle,
    Gate $gate,
    User $dispatcher,
    ?User $driver,
    string $status,
): Dispatch {
    return Dispatch::query()->create([
        'company_id' => $company->id,
        'vehicle_id' => $vehicle->id,
        'gate_id' => $gate->id,
        'plate_number' => $vehicle->plate_number,
        'pax_count' => 0,
        'bay_number' => '1',
        'remarks' => null,
        'dispatcher_user_id' => $dispatcher->id,
        'driver_user_id' => $driver?->id,
        'arrived_at' => now(),
        'departed_at' => $status === Dispatch::STATUS_DEPARTED ? now() : null,
        'dispatched_at' => now(),
        'status' => $status,
        'created_by' => $dispatcher->id,
        'updated_by' => $dispatcher->id,
    ]);
}

test('it rejects creating a dispatch when driver is already assigned today', function () {
    $ctx = makeDispatchContext();

    createDispatch(
        company: $ctx['company'],
        vehicle: $ctx['vehicleOne'],
        gate: $ctx['gate'],
        dispatcher: $ctx['dispatcher'],
        driver: $ctx['driverA'],
        status: Dispatch::STATUS_ARRIVED,
    );

    $this->actingAs($ctx['dispatcher'])
        ->post(route('company.dispatches.store'), [
            'vehicle_id' => $ctx['vehicleTwo']->id,
            'driver_user_id' => $ctx['driverA']->id,
            'gate_id' => $ctx['gate']->id,
            'bay_number' => 1,
            'remarks' => 'Duplicate should fail',
        ])
        ->assertSessionHasErrors('driver_user_id');
});

test('it rejects updating a dispatch when selected driver is already assigned today on another dispatch', function () {
    $ctx = makeDispatchContext();

    $dispatchToEdit = createDispatch(
        company: $ctx['company'],
        vehicle: $ctx['vehicleOne'],
        gate: $ctx['gate'],
        dispatcher: $ctx['dispatcher'],
        driver: $ctx['driverB'],
        status: Dispatch::STATUS_ARRIVED,
    );

    createDispatch(
        company: $ctx['company'],
        vehicle: $ctx['vehicleTwo'],
        gate: $ctx['gate'],
        dispatcher: $ctx['dispatcher'],
        driver: $ctx['driverA'],
        status: Dispatch::STATUS_ARRIVED,
    );

    $this->actingAs($ctx['dispatcher'])
        ->put(route('company.dispatches.update', $dispatchToEdit), [
            'vehicle_id' => $ctx['vehicleOne']->id,
            'driver_user_id' => $ctx['driverA']->id,
            'gate_id' => $ctx['gate']->id,
            'bay_number' => 1,
            'remarks' => 'Should fail update',
        ])
        ->assertSessionHasErrors('driver_user_id');
});

test('it allows updating dispatch while keeping the same driver on the same dispatch', function () {
    $ctx = makeDispatchContext();

    $dispatch = createDispatch(
        company: $ctx['company'],
        vehicle: $ctx['vehicleOne'],
        gate: $ctx['gate'],
        dispatcher: $ctx['dispatcher'],
        driver: $ctx['driverA'],
        status: Dispatch::STATUS_ARRIVED,
    );

    $this->actingAs($ctx['dispatcher'])
        ->put(route('company.dispatches.update', $dispatch), [
            'vehicle_id' => $ctx['vehicleOne']->id,
            'driver_user_id' => $ctx['driverA']->id,
            'gate_id' => $ctx['gate']->id,
            'bay_number' => 2,
            'remarks' => 'Allowed same driver update',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('dispatches', [
        'id' => $dispatch->id,
        'driver_user_id' => $ctx['driverA']->id,
        'bay_number' => '2',
        'remarks' => 'Allowed same driver update',
    ]);
});

test('it rejects driver change request when requested driver is already assigned today', function () {
    $ctx = makeDispatchContext();

    createDispatch(
        company: $ctx['company'],
        vehicle: $ctx['vehicleTwo'],
        gate: $ctx['gate'],
        dispatcher: $ctx['dispatcher'],
        driver: $ctx['driverA'],
        status: Dispatch::STATUS_ARRIVED,
    );

    $departedDispatch = createDispatch(
        company: $ctx['company'],
        vehicle: $ctx['vehicleOne'],
        gate: $ctx['gate'],
        dispatcher: $ctx['dispatcher'],
        driver: $ctx['driverB'],
        status: Dispatch::STATUS_DEPARTED,
    );

    $this->actingAs($ctx['dispatcher'])
        ->post(route('company.dispatches.change-requests.store', $departedDispatch), [
            'requested_field' => 'driver_user_id',
            'requested_value' => $ctx['driverA']->id,
            'reason' => 'Need this driver for this dispatch change request.',
        ])
        ->assertSessionHasErrors('requested_value');
});

test('it rejects creating a duplicate driver assignment when existing active dispatch has null dispatched_at', function () {
    $ctx = makeDispatchContext();

    createDispatch(
        company: $ctx['company'],
        vehicle: $ctx['vehicleOne'],
        gate: $ctx['gate'],
        dispatcher: $ctx['dispatcher'],
        driver: $ctx['driverA'],
        status: Dispatch::STATUS_ARRIVED,
    );

    Dispatch::query()
        ->where('company_id', $ctx['company']->id)
        ->where('vehicle_id', $ctx['vehicleOne']->id)
        ->update([
            'dispatched_at' => null,
            'arrived_at' => now(),
            'status' => Dispatch::STATUS_ARRIVED,
        ]);

    $this->actingAs($ctx['dispatcher'])
        ->post(route('company.dispatches.store'), [
            'vehicle_id' => $ctx['vehicleTwo']->id,
            'driver_user_id' => $ctx['driverA']->id,
            'gate_id' => $ctx['gate']->id,
            'bay_number' => 1,
            'remarks' => 'Should still fail with null dispatched_at fallback',
        ])
        ->assertSessionHasErrors('driver_user_id');
});
