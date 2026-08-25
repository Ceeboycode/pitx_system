<?php

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::query()->firstOrCreate(['name' => 'vehicles.toggleStatus', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'external_vehicles.toggleStatus', 'guard_name' => 'web']);

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
});

function vehicleStatusUser(string $roleName, array $permissions, ?int $companyId = null): User
{
    $role = Role::query()->create([
        'name' => $roleName,
        'guard_name' => 'web',
        'type' => $companyId === null ? 'internal' : 'external',
    ]);

    $role->givePermissionTo($permissions);

    $user = User::factory()->create([
        'company_id' => $companyId,
    ]);

    $user->assignRole($role);

    return $user;
}

test('admin suspension remark is stored separately from operator remark', function (): void {
    Notification::fake();

    $company = Company::factory()->verified()->create();
    $admin = vehicleStatusUser('vehicle-status-admin', ['vehicles.toggleStatus']);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'status' => Vehicle::STATUS_ACTIVE,
        'operator_remark' => 'Vehicle is under scheduled maintenance.',
        'suspension_remark' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('vehicles.toggle-status', $vehicle), [
            'status' => Vehicle::STATUS_SUSPENDED,
            'suspension_remark' => 'Registration documents have expired.',
        ])
        ->assertRedirect();

    $vehicle->refresh();

    expect($vehicle->status)->toBe(Vehicle::STATUS_SUSPENDED)
        ->and($vehicle->operator_remark)->toBe('Vehicle is under scheduled maintenance.')
        ->and($vehicle->suspension_remark)->toBe('Registration documents have expired.');
});

test('external operator cannot suspend a vehicle or modify suspension remark', function (): void {
    Notification::fake();

    $company = Company::factory()->verified()->create();
    $operator = vehicleStatusUser('vehicle-status-operator', ['external_vehicles.toggleStatus'], $company->id);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'status' => Vehicle::STATUS_ACTIVE,
        'operator_remark' => 'Original operator note.',
        'suspension_remark' => 'Existing admin note.',
    ]);

    $this->actingAs($operator)
        ->patch(route('company.vehicles.toggle-status', $vehicle), [
            'status' => Vehicle::STATUS_SUSPENDED,
            'operator_remark' => 'Trying to suspend.',
            'suspension_remark' => 'Operator should not write this.',
        ])
        ->assertSessionHasErrors(['status', 'suspension_remark']);

    $vehicle->refresh();

    expect($vehicle->status)->toBe(Vehicle::STATUS_ACTIVE)
        ->and($vehicle->operator_remark)->toBe('Original operator note.')
        ->and($vehicle->suspension_remark)->toBe('Existing admin note.');
});

test('external operator inactive remark does not overwrite suspension remark', function (): void {
    Notification::fake();

    $company = Company::factory()->verified()->create();
    $operator = vehicleStatusUser('vehicle-status-operator-two', ['external_vehicles.toggleStatus'], $company->id);

    $vehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'status' => Vehicle::STATUS_ACTIVE,
        'operator_remark' => null,
        'suspension_remark' => 'Prior admin suspension reason.',
    ]);

    expect($vehicle->status)->toBe(Vehicle::STATUS_ACTIVE);

    $this->actingAs($operator)
        ->patch(route('company.vehicles.toggle-status', $vehicle), [
            'status' => Vehicle::STATUS_INACTIVE,
            'operator_remark' => 'Vehicle is under scheduled maintenance.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Vehicle set to inactive.');

    $vehicle->refresh();

    expect($vehicle->status)->toBe(Vehicle::STATUS_INACTIVE)
        ->and($vehicle->operator_remark)->toBe('Vehicle is under scheduled maintenance.')
        ->and($vehicle->suspension_remark)->toBe('Prior admin suspension reason.');
});
