<?php

use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    Storage::fake('public');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('seeds stable data idempotently without changing existing internal credentials', function (): void {
    $existingInternalUser = User::factory()->create([
        'username' => '2026-0001',
        'email' => 'preserved-internal@example.test',
        'phone_number' => '+639170099001',
        'password' => Hash::make('preserved-password'),
    ]);
    $passwordHash = $existingInternalUser->password;

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $existingInternalUser->refresh();

    expect($existingInternalUser->email)->toBe('preserved-internal@example.test')
        ->and($existingInternalUser->password)->toBe($passwordHash)
        ->and($existingInternalUser->hasRole('super-admin'))->toBeTrue()
        ->and(User::query()->where('username', 'northstar-operator')->count())->toBe(1)
        ->and(Company::query()->whereIn('company_code', ['NORTHSTAR', 'PENDING', 'INCOMPLETE', 'ISSUE', 'EXPIRED', 'SUSPENDED'])->count())->toBe(6)
        ->and(VehicleType::query()->where('type_name', 'Coach Bus')->count())->toBe(1);
});

it('creates role-specific permissions with the web guard and keeps external access separate', function (): void {
    $this->seed(DatabaseSeeder::class);

    $superAdmin = User::query()->where('username', '2026-0001')->firstOrFail();
    $operator = User::query()->where('username', 'northstar-operator')->firstOrFail();
    $driver = User::query()->where('username', 'northstar-driver')->firstOrFail();

    expect(Permission::query()->where('name', 'company_documents.view')->value('guard_name'))->toBe('web')
        ->and($superAdmin->can('companies.forceDelete'))->toBeTrue()
        ->and($superAdmin->getAllPermissions()->pluck('name'))->not->toContain('external_vehicles.create')
        ->and($operator->can('external_vehicles.create'))->toBeTrue()
        ->and($superAdmin->getAllPermissions()->pluck('name'))->not->toContain('vehicles.create')
        ->and(Permission::query()->where('name', 'vehicles.create')->exists())->toBeFalse()
        ->and($driver->getAllPermissions())->toBeEmpty();
});

it('creates company and document scenarios with consistent statuses', function (): void {
    $this->seed(DatabaseSeeder::class);

    $companies = Company::query()->whereIn('company_code', ['NORTHSTAR', 'PENDING', 'INCOMPLETE', 'ISSUE', 'EXPIRED', 'SUSPENDED'])->get()->keyBy('company_code');

    expect($companies['NORTHSTAR']->status)->toBe(Company::STATUS_VERIFIED)
        ->and($companies['NORTHSTAR']->is_active)->toBeTrue()
        ->and($companies['PENDING']->status)->toBe(Company::STATUS_FOR_VERIFICATION)
        ->and($companies['INCOMPLETE']->status)->toBe(Company::STATUS_DRAFT)
        ->and($companies['ISSUE']->status)->toBe(Company::STATUS_NEEDS_REVISION)
        ->and($companies['EXPIRED']->status)->toBe(Company::STATUS_NEEDS_REVISION)
        ->and($companies['SUSPENDED']->status)->toBe(Company::STATUS_VERIFIED)
        ->and($companies['SUSPENDED']->is_active)->toBeFalse()
        ->and($companies['EXPIRED']->documents()->where('status', 'expired')->exists())->toBeTrue()
        ->and($companies['ISSUE']->documents()->where('status', 'invalid')->exists())->toBeTrue();
});

it('keeps vehicles related to valid vehicle types and company boundaries', function (): void {
    $this->seed(DatabaseSeeder::class);

    $activeVehicle = Vehicle::query()->where('plate_number', 'DEV-NOR-100')->with('vehicleType')->firstOrFail();
    $historicalVehicle = Vehicle::query()->where('plate_number', 'DEV-SUS-100')->with(['company', 'vehicleType'])->firstOrFail();
    $issueVehicle = Vehicle::query()->where('plate_number', 'DEV-ISS-100')->firstOrFail();

    expect($activeVehicle->vehicleType?->is_active)->toBeTrue()
        ->and($activeVehicle->company->company_code)->toBe('NORTHSTAR')
        ->and($historicalVehicle->vehicleType?->type_name)->toBe('Historic Coach')
        ->and($historicalVehicle->vehicleType?->is_active)->toBeFalse()
        ->and($historicalVehicle->company->company_code)->toBe('SUSPENDED')
        ->and($issueVehicle->verification_status)->toBe(Vehicle::VERIFICATION_STATUS_NEEDS_REVISION)
        ->and($issueVehicle->status)->toBe(Vehicle::STATUS_INACTIVE);
});

it('denies login for an active external user whose only company is inactive', function (): void {
    $this->seed(DatabaseSeeder::class);

    $response = $this->post(route('login.store'), [
        'login' => 'pending.operator@example.test',
        'password' => 'pitx@123',
    ]);

    $this->assertGuest();
    $response->assertRedirect();
});

it('does not seed demo data when the application environment is production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    app(DatabaseSeeder::class)->run();

    expect(Company::query()->count())->toBe(0)
        ->and(Permission::query()->where('name', 'companies.viewAny')->exists())->toBeTrue();

    app()->detectEnvironment(fn (): string => 'testing');
});
