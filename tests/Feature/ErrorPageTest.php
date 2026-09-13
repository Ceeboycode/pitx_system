<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function userWithRoleType(string $type): User
{
    $role = Role::query()->create([
        'name' => fake()->unique()->slug(2),
        'guard_name' => 'web',
        'type' => $type,
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('missing pages render the Inertia error component with a 404 status', function () {
    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Error')
            ->where('status', 404)
        );
});

test('api routes still return a json 404 instead of the Inertia error page', function () {
    $this->getJson('/api/v1/this-route-does-not-exist')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');
});

test('guests receive a null auth user so the dashboard button is hidden', function () {
    $this->get('/this-route-does-not-exist')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user', null)
        );
});

test('internal users are routed to the internal portal', function () {
    $this->actingAs(userWithRoleType('internal'))
        ->get('/this-route-does-not-exist')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.role_type', 'internal')
        );
});

test('external users are routed to the company portal', function () {
    $this->actingAs(userWithRoleType('external'))
        ->get('/this-route-does-not-exist')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.role_type', 'external')
        );
});
