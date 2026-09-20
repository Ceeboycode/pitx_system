<?php

use App\Models\Company;
use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        'vehicle_types.view',
        'vehicle_types.create',
        'vehicle_types.update',
        'vehicle_types.archive',
        'vehicle_types.restore',
        'vehicle_types.viewTrash',
        'vehicle_types.forceDelete',
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
        'vehicle_types.view',
        'vehicle_types.create',
        'vehicle_types.update',
        'vehicle_types.archive',
        'vehicle_types.restore',
        'vehicle_types.viewTrash',
        'vehicle_types.forceDelete',
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
            ->component('VehicleType/Index'));

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

test('duplicate names and assigned types cannot be archived', function (): void {
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

    $activeVehicle = Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'vehicle_type_id' => VehicleType::factory()->create(['created_by' => $user->id])->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->put(route('vehicles.update', $activeVehicle), [
            ...$data,
            'plate_number' => $activeVehicle->plate_number,
            'body_number' => $activeVehicle->body_number,
        ])
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

test('an authorized user can archive an unassigned vehicle type', function (): void {
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->delete(route('vehicle-types.destroy', $vehicleType))
        ->assertRedirect(route('vehicle-types.index'));

    expect(VehicleType::find($vehicleType->id))->toBeNull();

    $trashed = VehicleType::onlyTrashed()->findOrFail($vehicleType->id);
    expect($trashed->deleted_by)->toBe($user->id);
});

test('an archived vehicle type can be restored', function (): void {
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['created_by' => $user->id]);
    $vehicleType->delete();

    $this->actingAs($user)
        ->post(route('vehicle-types.restore', $vehicleType))
        ->assertRedirect();

    expect(VehicleType::find($vehicleType->id))->not->toBeNull();
});

test('a trashed vehicle type still assigned to a vehicle cannot be force deleted', function (): void {
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['created_by' => $user->id]);
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
    $vehicleType->delete();

    $this->actingAs($user)
        ->delete(route('vehicle-types.forceDelete', $vehicleType))
        ->assertSessionHasErrors('vehicle_type');

    $this->assertModelExists($vehicleType);
});

test('an authorized user can permanently delete an unassigned trashed vehicle type', function (): void {
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['created_by' => $user->id]);
    $vehicleType->delete();

    $this->actingAs($user)
        ->delete(route('vehicle-types.forceDelete', $vehicleType))
        ->assertRedirect();

    $this->assertDatabaseMissing('vehicle_types', ['id' => $vehicleType->id]);
});

test('a vehicle type picture and description can be uploaded and later removed', function (): void {
    Storage::fake('public');
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('vehicle-types.update', $vehicleType), [
            'type_name' => $vehicleType->type_name,
            'description' => 'A large passenger bus used on long-haul routes.',
            'picture' => UploadedFile::fake()->image('bus.jpg'),
        ])
        ->assertRedirect();

    $vehicleType->refresh();
    expect($vehicleType->description)->toBe('A large passenger bus used on long-haul routes.');
    expect($vehicleType->picture_path)->not->toBeNull();
    Storage::disk('public')->assertExists($vehicleType->picture_path);

    $this->actingAs($user)
        ->put(route('vehicle-types.update', $vehicleType), [
            'type_name' => $vehicleType->type_name,
            'remove_picture' => true,
        ])
        ->assertRedirect();

    expect($vehicleType->fresh()->picture_path)->toBeNull();
});

test('the edit page exposes vehicle stats, recent vehicles, and audit history', function (): void {
    $user = vehicleTypeManager();
    $vehicleType = VehicleType::factory()->create(['created_by' => $user->id]);
    $company = Company::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
    $gate = Gate::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
    $route = Route::factory()->create(['gate_id' => $gate->id, 'created_by' => $user->id, 'updated_by' => $user->id]);
    Vehicle::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'vehicle_type_id' => $vehicleType->id,
        'status' => Vehicle::STATUS_ACTIVE,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('vehicle-types.edit', $vehicleType))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('VehicleType/Edit')
            ->where('vehicleStats.total', 1)
            ->where('vehicleStats.active', 1)
            ->has('recentVehicles', 1)
            ->has('auditLogs'));
});

test('the trash listing requires the viewTrash permission', function (): void {
    $user = vehicleTypeManager();

    $this->actingAs($user)
        ->get(route('vehicle-types.trash'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('VehicleType/Trash'));

    $user->revokePermissionTo('vehicle_types.viewTrash');

    $this->actingAs($user)
        ->get(route('vehicle-types.trash'))
        ->assertForbidden();
});
