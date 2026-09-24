<?php

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['operator', 'driver'] as $roleName) {
        Role::query()->firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['type' => 'external'],
        );
    }

    foreach ([
        'external_users.view',
        'external_users.update',
        'external_users.toggleStatus',
        'external_users.resetPassword',
        'external_users.archive',
    ] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::query()->where('name', 'operator')->first()->syncPermissions([
        'external_users.view',
        'external_users.update',
        'external_users.toggleStatus',
        'external_users.resetPassword',
        'external_users.archive',
    ]);

    $this->company = Company::factory()->verified()->create();

    $this->operator = User::factory()->create(['company_id' => $this->company->id]);
    $this->operator->assignRole('operator');

    $this->employee = User::factory()->create(['company_id' => $this->company->id]);
    $this->employee->assignRole('driver');
});

test('the employee-users show route no longer exists', function (): void {
    expect(Route::has('employee-users.show'))->toBeFalse();
});

test('operator can open the unified edit page for another employee', function (): void {
    $this->actingAs($this->operator)
        ->get(route('employee-users.edit', $this->employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Employee/Edit')
            ->where('employee.id', $this->employee->id)
            ->has('roles')
        );
});

test('a driver employee always gets the vehicles and dispatches tabs', function (): void {
    $this->actingAs($this->operator)
        ->get(route('employee-users.edit', $this->employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Employee/Edit')
            ->where('tabAccess.vehicles', true)
            ->where('tabAccess.dispatches', true)
        );
});

test('an employee role without vehicle or dispatch permissions does not get those tabs', function (): void {
    $this->actingAs($this->operator)
        ->get(route('employee-users.edit', $this->operator))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Employee/Edit')
            ->where('tabAccess.vehicles', false)
            ->where('tabAccess.dispatches', false)
        );
});

test('operator can open the edit page for their own account', function (): void {
    $this->actingAs($this->operator)
        ->get(route('employee-users.edit', $this->operator))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('External/Employee/Edit')
            ->where('employee.id', $this->operator->id)
        );
});

test('user without the view permission cannot open the edit page', function (): void {
    $viewer = User::factory()->create(['company_id' => $this->company->id]);
    $viewer->assignRole('driver');

    $this->actingAs($viewer)
        ->get(route('employee-users.edit', $this->employee))
        ->assertForbidden();
});

test('operator can update their own basic details', function (): void {
    $this->actingAs($this->operator)
        ->put(route('employee-users.update', $this->operator), [
            'name' => 'Updated Operator Name',
            // A real, DNS-deliverable domain: Faker's default `safeEmail()` domains
            // (example.com/.net/.org) publish "no mail accepted" DNS records and
            // correctly fail the controller's `email:rfc,dns` validation rule.
            'email' => 'updated-operator@gmail.com',
            'phone_number' => $this->operator->phone_number,
            'role' => 'operator',
        ])
        ->assertRedirect(route('employee-users.index'));

    expect($this->operator->fresh()->name)->toBe('Updated Operator Name');
});

test('operator can update another employee\'s basic details', function (): void {
    $this->actingAs($this->operator)
        ->put(route('employee-users.update', $this->employee), [
            'name' => 'Updated Employee Name',
            'email' => 'updated-employee@gmail.com',
            'phone_number' => $this->employee->phone_number,
            'role' => 'driver',
        ])
        ->assertRedirect(route('employee-users.index'));

    expect($this->employee->fresh()->name)->toBe('Updated Employee Name');
});

test('operator still cannot toggle their own status', function (): void {
    $this->actingAs($this->operator)
        ->patch(route('employee-users.toggle-status', $this->operator))
        ->assertForbidden();
});

test('operator still cannot reset their own password', function (): void {
    $this->actingAs($this->operator)
        ->patch(route('employee-users.reset-password', $this->operator))
        ->assertForbidden();
});

test('operator still cannot archive their own account', function (): void {
    $this->actingAs($this->operator)
        ->delete(route('employee-users.destroy', $this->operator))
        ->assertForbidden();
});

test('operator can toggle another employee\'s status', function (): void {
    $this->actingAs($this->operator)
        ->patch(route('employee-users.toggle-status', $this->employee))
        ->assertRedirect();

    expect($this->employee->fresh()->status)->not->toBe($this->employee->status);
});
