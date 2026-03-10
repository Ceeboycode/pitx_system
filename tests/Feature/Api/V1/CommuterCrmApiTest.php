<?php

use App\Models\CrmMessage;
use App\Models\CrmThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function commuterUser(array $attributes = []): array
{
    $role = Role::firstOrCreate([
        'name' => 'commuter',
        'guard_name' => 'web',
    ], [
        'type' => 'commuter',
    ]);

    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return [$user, $role];
}

test('commuter can login and receive bearer token', function () {
    [$user] = commuterUser([
        'email' => 'commuter@example.com',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', $user->email);

    expect($user->fresh()->api_token)->not->toBeNull();
});

test('commuter can register and receive bearer token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'New Commuter',
        'email' => 'new.commuter@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'new.commuter@example.com');

    $user = User::query()->where('email', 'new.commuter@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('commuter'))->toBeTrue();
    expect($user->api_token)->not->toBeNull();
});

test('non commuter users are blocked from commuter login', function () {
    Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ], [
        'type' => 'internal',
    ]);

    $admin = User::factory()->create([
        'email' => 'admin@example.com',
    ]);
    $admin->assignRole('admin');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $response->assertForbidden();
});

test('commuter can create and list own crm threads', function () {
    [$user] = commuterUser();
    $plainToken = 'token-for-commuter-thread-test';
    $user->forceFill([
        'api_token' => hash('sha256', $plainToken),
    ])->save();

    $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->postJson('/api/v1/crm/threads', [
            'category' => 'system',
            'subject' => 'App is crashing on startup',
            'body' => 'The app exits right after I tap open.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.subject', 'App is crashing on startup');

    $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->getJson('/api/v1/crm/threads');

    $response->assertOk()
        ->assertJsonPath('data.0.created_by_user_id', $user->id);

    expect(CrmThread::query()->where('created_by_user_id', $user->id)->count())->toBe(1);
    expect(CrmMessage::query()->count())->toBe(1);
});

test('commuter cannot access other commuter thread', function () {
    [$owner] = commuterUser(['email' => 'owner@example.com']);
    [$viewer] = commuterUser(['email' => 'viewer@example.com']);

    $thread = CrmThread::query()->create([
        'company_id' => null,
        'created_by_user_id' => $owner->id,
        'category' => 'compliance',
        'subject' => 'Ownership scoped thread',
        'last_message_at' => now(),
    ]);

    $plainToken = 'token-for-viewer-thread-test';
    $viewer->forceFill([
        'api_token' => hash('sha256', $plainToken),
    ])->save();

    $this->withHeader('Authorization', 'Bearer '.$plainToken)
        ->getJson('/api/v1/crm/threads/'.$thread->id)
        ->assertForbidden();
});
