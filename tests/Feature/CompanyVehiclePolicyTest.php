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
        'external_vehicles.viewAny',
        'external_vehicles.view',
        'external_vehicles.create',
        'external_vehicles.update',
        'external_vehicles.toggleStatus',
        'external_vehicle_documents.download',
        'external_vehicle_documents.upload',
    ] as $perm) {
        Permission::query()->firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    $this->operatorRole = Role::query()->where('name', 'operator')->first();

    $this->operatorRole->syncPermissions([
        'external_vehicles.viewAny',
        'external_vehicles.view',
        'external_vehicles.create',
        'external_vehicles.update',
        'external_vehicles.toggleStatus',
        'external_vehicle_documents.download',
        'external_vehicle_documents.upload',
    ]);
});

function createOperatorUser(Company $company): User
{
    $role = Role::query()->where('name', 'operator')->first();
    $user = User::factory()->create([
        'company_id' => $company->id,
    ]);
    $user->assignRole($role);

    return $user;
}

test('policy before hook grants access to admins and returns null for operators', function (): void {
    $policy = new \App\Policies\CompanyVehiclePolicy;

    $company = Company::factory()->verified()->create();
    $operator = createOperatorUser($company);

    $adminRole = Role::query()->where('name', 'admin')->first();
    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    expect($policy->before($admin, 'view'))->toBeTrue()
        ->and($policy->before($operator, 'view'))->toBeNull();
});

test('operator can view their own company vehicles and show page', function (): void {
    $companyA = Company::factory()->verified()->create();
    $operatorA = createOperatorUser($companyA);

    $vehicleA = Vehicle::factory()->create([
        'company_id' => $companyA->id,
        'plate_number' => 'COMPA-01',
    ]);

    $policy = new \App\Policies\CompanyVehiclePolicy;
    expect($policy->view($operatorA, $vehicleA))->toBeTrue()
        ->and($policy->update($operatorA, $vehicleA))->toBeTrue()
        ->and($policy->toggleStatus($operatorA, $vehicleA))->toBeTrue();

    $this->actingAs($operatorA)
        ->get(route('company.vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.plate_number', 'COMPA-01')
        );

    $this->actingAs($operatorA)
        ->get(route('company.vehicles.show', $vehicleA))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Vehicles/Show')
            ->where('vehicle.plate_number', 'COMPA-01')
        );
});

test('operator cannot view or edit another company vehicle', function (): void {
    $companyA = Company::factory()->verified()->create();
    $companyB = Company::factory()->verified()->create();

    $operatorA = createOperatorUser($companyA);

    $vehicleB = Vehicle::factory()->create([
        'company_id' => $companyB->id,
        'plate_number' => 'COMPB-01',
    ]);

    $policy = new \App\Policies\CompanyVehiclePolicy;
    expect($policy->view($operatorA, $vehicleB))->toBeFalse()
        ->and($policy->update($operatorA, $vehicleB))->toBeFalse()
        ->and($policy->toggleStatus($operatorA, $vehicleB))->toBeFalse();

    // Operator A receives 404 Not Found due to tenant isolation
    $this->actingAs($operatorA)
        ->get(route('company.vehicles.show', $vehicleB))
        ->assertNotFound();

    $this->actingAs($operatorA)
        ->get(route('company.vehicles.edit', $vehicleB))
        ->assertNotFound();

    // Operator A cannot update Vehicle B (Forbidden by policy/request authorization)
    $this->actingAs($operatorA)
        ->put(route('company.vehicles.update', $vehicleB), [
            'plate_number' => 'HACKED-01',
        ])
        ->assertForbidden();
});
