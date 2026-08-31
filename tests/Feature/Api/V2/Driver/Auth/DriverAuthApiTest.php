<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function driverRole(): \Spatie\Permission\Models\Role
{
    return Role::firstOrCreate([
        'name' => 'driver',
        'guard_name' => 'web',
    ], [
        'type' => 'driver',
    ]);
}

test('driver can register and receive a sanctum token', function (): void {
    $response = $this->postJson('/api/v2/driver/auth/register', [
        'name' => 'Driver One',
        'email' => 'driver.one@example.com',
        'username' => 'driver_one',
        'password' => 'password12345',
        'password_confirmation' => 'password12345',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'driver.one@example.com');

    $user = User::query()->where('email', 'driver.one@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('driver'))->toBeTrue();
    expect($user->tokens()->count())->toBe(1);
});

test('driver can login and access their profile', function (): void {
    $role = driverRole();
    $user = User::factory()->create([
        'email' => 'driver.login@example.com',
        'username' => 'driver_login',
        'password' => bcrypt('password123'),
    ]);
    $user->assignRole($role);

    $loginResponse = $this->postJson('/api/v2/driver/auth/login', [
        'login' => 'driver.login@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer');

    $token = $loginResponse->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v2/driver/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'driver.login@example.com');
});
