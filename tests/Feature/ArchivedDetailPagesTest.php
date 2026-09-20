<?php

use App\Models\Company;
use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Roles the model factories and Company::operator() look up, plus one admin to credit as creator.
    foreach (['admin', 'it', 'terminal manager'] as $name) {
        Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['type' => 'internal']);
    }
    foreach (['operator', 'dispatcher', 'driver'] as $name) {
        Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['type' => 'external']);
    }
    User::factory()->create()->assignRole('admin');

    foreach ([
        'gates.view', 'gates.viewTrash', 'gates.update', 'gates.archive',
        'routes.view', 'routes.viewTrash', 'routes.update', 'routes.archive',
        'vehicle_types.view', 'vehicle_types.viewTrash', 'vehicle_types.update', 'vehicle_types.archive',
        'roles.view', 'roles.viewTrash', 'roles.update', 'roles.archive',
        'users.view', 'users.viewTrash', 'users.update', 'users.archive',
        'companies.view', 'companies.viewAny', 'companies.update', 'companies.archive',
        'vehicles.view', 'vehicles.viewAny', 'vehicles.update', 'vehicles.archive',
    ] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

/**
 * Everything the archived-detail tests need to know about one entity.
 *
 * @return array{record: object, show: string, component: string, view: array<int, string>, archives: array<int, string>, put: string, delete: string}
 */
function archivedDetailCase(string $entity, bool $archived = true): array
{
    $record = match ($entity) {
        'gate' => Gate::factory()->create(),
        'route' => (function () {
            Gate::factory()->create();

            return Route::factory()->create();
        })(),
        'vehicle type' => VehicleType::factory()->create(),
        'role' => Role::query()->create(['name' => 'detail-test-role', 'guard_name' => 'web', 'type' => 'external']),
        'user' => User::factory()->create(),
        'company' => Company::factory()->create(),
        'vehicle' => (function () {
            Gate::factory()->create();

            return Vehicle::factory()->create([
                'company_id' => Company::factory()->create()->id,
                'route_id' => Route::factory()->create()->id,
            ]);
        })(),
    };

    if ($archived) {
        $record->delete();
    }

    return match ($entity) {
        'gate' => ['record' => $record, 'show' => 'gates.edit', 'component' => 'Gates/Edit', 'view' => ['gates.view'], 'archives' => ['gates.viewTrash'], 'put' => 'gates.update', 'delete' => 'gates.destroy'],
        'route' => ['record' => $record, 'show' => 'routes.edit', 'component' => 'Route/Edit', 'view' => ['routes.view'], 'archives' => ['routes.viewTrash'], 'put' => 'routes.update', 'delete' => 'routes.destroy'],
        'vehicle type' => ['record' => $record, 'show' => 'vehicle-types.edit', 'component' => 'VehicleType/Edit', 'view' => ['vehicle_types.view'], 'archives' => ['vehicle_types.viewTrash'], 'put' => 'vehicle-types.update', 'delete' => 'vehicle-types.destroy'],
        'role' => ['record' => $record, 'show' => 'roles.edit', 'component' => 'Roles/Edit', 'view' => ['roles.view'], 'archives' => ['roles.viewTrash'], 'put' => 'roles.update', 'delete' => 'roles.destroy'],
        'user' => ['record' => $record, 'show' => 'users.show', 'component' => 'Users/Edit', 'view' => ['users.view'], 'archives' => ['users.viewTrash'], 'put' => 'users.update', 'delete' => 'users.destroy'],
        'company' => ['record' => $record, 'show' => 'companies.show', 'component' => 'Company/Show', 'view' => ['companies.view'], 'archives' => ['companies.viewAny'], 'put' => 'companies.update', 'delete' => 'companies.destroy'],
        'vehicle' => ['record' => $record, 'show' => 'vehicles.show', 'component' => 'Vehicles/Show', 'view' => ['vehicles.view'], 'archives' => ['vehicles.viewAny'], 'put' => 'vehicles.update', 'delete' => 'vehicles.destroy'],
    };
}

/**
 * @param  array<int, string>  $permissions
 */
function archivedDetailViewer(array $permissions): User
{
    $role = Role::query()->create([
        'name' => 'archived-viewer-'.str(implode('-', $permissions))->slug(),
        'guard_name' => 'web',
        'type' => 'internal',
    ]);
    $role->givePermissionTo($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

dataset('archivable entities', ['gate', 'route', 'vehicle type', 'role', 'user', 'company', 'vehicle']);

it('opens the detail page of an archived record read-only for someone who may open the archives', function (string $entity): void {
    $case = archivedDetailCase($entity);
    $viewer = archivedDetailViewer([...$case['view'], ...$case['archives']]);

    $this->actingAs($viewer)
        ->get(route($case['show'], $case['record']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component($case['component'])
            ->where('isArchived', true));
})->with('archivable entities');

it('does not open an archived record for someone who may not open the archives', function (string $entity): void {
    $case = archivedDetailCase($entity);
    $viewer = archivedDetailViewer($case['view']);

    $this->actingAs($viewer)
        ->get(route($case['show'], $case['record']))
        ->assertForbidden();
})->with('archivable entities');

it('does not flag an active record as archived', function (string $entity): void {
    $case = archivedDetailCase($entity, archived: false);
    $viewer = archivedDetailViewer([...$case['view'], ...$case['archives']]);

    $this->actingAs($viewer)
        ->get(route($case['show'], $case['record']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component($case['component'])
            ->where('isArchived', false));
})->with('archivable entities');

it('still refuses to update or archive an archived record', function (string $entity): void {
    $case = archivedDetailCase($entity);
    $editor = archivedDetailViewer([
        ...$case['view'],
        ...$case['archives'],
        ...match ($entity) {
            'gate' => ['gates.update', 'gates.archive'],
            'route' => ['routes.update', 'routes.archive'],
            'vehicle type' => ['vehicle_types.update', 'vehicle_types.archive'],
            'role' => ['roles.update', 'roles.archive'],
            'user' => ['users.update', 'users.archive'],
            'company' => ['companies.update', 'companies.archive'],
            'vehicle' => ['vehicles.update', 'vehicles.archive'],
        },
    ]);

    // Route model binding leaves archived records out of every route that is not a detail page,
    // so these fail before any validation or controller code runs.
    $this->actingAs($editor)
        ->put(route($case['put'], $case['record']), [])
        ->assertNotFound();

    $this->actingAs($editor)
        ->delete(route($case['delete'], $case['record']))
        ->assertNotFound();
})->with('archivable entities');
