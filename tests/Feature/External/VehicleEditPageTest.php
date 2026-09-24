<?php

use App\Models\Company;
use App\Models\Gate;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Route as RouteFacade;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

const VEHICLE_DOCUMENT_TYPES = [
    'insurance_certificate',
    'cpc',
    'official_receipt',
    'certificate_of_registration',
    'puv_identification_markings',
];

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::query()->firstOrCreate(['name' => 'operator', 'guard_name' => 'web'], ['type' => 'external']);
    Role::query()->firstOrCreate(['name' => 'driver', 'guard_name' => 'web'], ['type' => 'external']);

    // VehicleFactory and RouteFactory look up their creator by these role names.
    foreach (['admin', 'it', 'terminal manager'] as $roleName) {
        Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web'], ['type' => 'internal']);
    }

    foreach (['external_vehicles.view', 'external_vehicles.update'] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::query()->where('name', 'operator')->first()->syncPermissions(['external_vehicles.view', 'external_vehicles.update']);
    Role::query()->where('name', 'driver')->first()->syncPermissions(['external_vehicles.view']);

    $this->company = Company::factory()->verified()->create();

    $this->operator = User::factory()->create(['company_id' => $this->company->id]);
    $this->operator->assignRole('operator');

    $this->viewer = User::factory()->create(['company_id' => $this->company->id]);
    $this->viewer->assignRole('driver');

    $this->route = Route::factory()->create(['status' => 'active', 'gate_id' => Gate::factory()->create()->id]);

    $this->vehicle = Vehicle::factory()->create([
        'company_id' => $this->company->id,
        'route_id' => $this->route->id,
        'status' => 'active',
        'verification_status' => 'verified',
        'color' => 'White',
    ]);

    foreach (VEHICLE_DOCUMENT_TYPES as $type) {
        VehicleDocument::query()->create([
            'vehicle_id' => $this->vehicle->id,
            'document_type' => $type,
            'file_path' => "documents/{$type}.pdf",
            'file_name' => "{$type}.pdf",
            'file_mime_type' => 'application/pdf',
            'file_size' => 1024,
            'status' => 'verified',
            'issued_at' => now()->subMonth()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'created_by' => $this->operator->id,
        ]);
    }
});

/**
 * The payload the update form always sends: the vehicle's own values and one entry per required document.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function vehicleUpdatePayload(Vehicle $vehicle, array $overrides = []): array
{
    return array_merge([
        'vehicle_type_id' => $vehicle->vehicle_type_id,
        'plate_number' => $vehicle->plate_number,
        'body_number' => $vehicle->body_number,
        'capacity' => $vehicle->capacity,
        'color' => $vehicle->color,
        'engine_number' => $vehicle->engine_number,
        'chassis_number' => $vehicle->chassis_number,
        'make_model' => $vehicle->make_model,
        'route_id' => $vehicle->route_id,
        'documents' => $vehicle->documents()->get()->map(fn (VehicleDocument $document): array => [
            'id' => $document->id,
            'document_type' => $document->document_type,
            'issued_at' => $document->issued_at?->toDateString(),
            'expires_at' => $document->expires_at?->toDateString(),
        ])->all(),
    ], $overrides);
}

test('the vehicle show route no longer exists', function (): void {
    expect(RouteFacade::has('company.vehicles.show'))->toBeFalse();
});

test('an operator with only the view permission can open the vehicle page', function (): void {
    $this->actingAs($this->viewer)
        ->get(route('company.vehicles.edit', $this->vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Vehicles/Edit')
            ->where('vehicle.id', $this->vehicle->id)
            ->has('vehicle.documents', 5)
            ->has('vehicle.documents.0.download_url')
            ->has('history')
            ->has('dispatches')
            ->has('routes')
        );
});

test('a suspended vehicle can still be opened', function (): void {
    $this->vehicle->update(['status' => 'suspended']);

    $this->actingAs($this->operator)
        ->get(route('company.vehicles.edit', $this->vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('vehicle.status', 'suspended'));
});

test('the current route and an inactive vehicle type stay selectable', function (): void {
    $inactiveType = VehicleType::factory()->create(['is_active' => false]);
    $otherInactiveType = VehicleType::factory()->create(['is_active' => false]);
    $this->route->update(['status' => 'inactive']);
    $this->vehicle->update(['vehicle_type_id' => $inactiveType->id]);

    $this->actingAs($this->operator)
        ->get(route('company.vehicles.edit', $this->vehicle))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($inactiveType, $otherInactiveType): void {
            $props = $page->toArray()['props'];

            expect(collect($props['vehicleTypes'])->pluck('id'))
                ->toContain($inactiveType->id)
                ->not->toContain($otherInactiveType->id)
                ->and(collect($props['routes'])->pluck('id'))->toContain($this->route->id);
        });
});

test('dispatches are only sent to accounts that may view them', function (): void {
    $this->actingAs($this->operator)
        ->get(route('company.vehicles.edit', $this->vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('dispatches', 0));
});

test('operator can update vehicle details without resubmitting documents', function (): void {
    $this->actingAs($this->operator)
        ->put(route('company.vehicles.update', $this->vehicle), vehicleUpdatePayload($this->vehicle, ['color' => 'Blue']))
        ->assertRedirect(route('company.vehicles.edit', $this->vehicle));

    $vehicle = $this->vehicle->fresh();

    expect($vehicle->color)->toBe('Blue')
        ->and($vehicle->status)->toBe('inactive')
        ->and($vehicle->verification_status)->toBe('for_verification')
        ->and($vehicle->verification_remark)->toBe('Pending review: vehicle details were updated.');
});

test('saving without any change is rejected', function (): void {
    $this->actingAs($this->operator)
        ->put(route('company.vehicles.update', $this->vehicle), vehicleUpdatePayload($this->vehicle))
        ->assertSessionHasErrors('documents');

    expect($this->vehicle->fresh()->status)->toBe('active');
});

test('an account without the update permission cannot update a vehicle', function (): void {
    $this->actingAs($this->viewer)
        ->put(route('company.vehicles.update', $this->vehicle), vehicleUpdatePayload($this->vehicle, ['color' => 'Blue']))
        ->assertForbidden();

    expect($this->vehicle->fresh()->color)->toBe('White');
});

test('a detail change shows up in the history without staff names or file paths', function (): void {
    $this->actingAs($this->operator)
        ->put(route('company.vehicles.update', $this->vehicle), vehicleUpdatePayload($this->vehicle, ['color' => 'Blue']))
        ->assertRedirect();

    $this->actingAs($this->operator)
        ->get(route('company.vehicles.edit', $this->vehicle))
        ->assertOk()
        ->assertInertia(function (Assert $page): void {
            $history = collect($page->toArray()['props']['history']);
            $colorChange = $history
                ->where('subject', 'vehicle')
                ->where('action', 'updated')
                ->flatMap(fn (array $entry) => $entry['changes'])
                ->firstWhere('field', 'color');

            expect($colorChange)->not->toBeNull()
                ->and($colorChange['old'])->toBe('White')
                ->and($colorChange['new'])->toBe('Blue')
                ->and($history->flatMap(fn (array $entry) => collect($entry['changes'])->pluck('field'))->all())
                ->not->toContain('file_path')
                ->not->toContain('created_by');
        });
});
