<?php

use App\Mail\TemporaryPasswordMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function passwordResetAdmin(): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $role = Role::query()->create([
        'name' => 'admin',
        'guard_name' => 'web',
        'type' => 'internal',
    ]);

    $permission = Permission::query()->firstOrCreate([
        'name' => 'users.resetPassword',
        'guard_name' => 'web',
    ]);

    $role->givePermissionTo($permission);

    $admin = User::factory()->create();
    $admin->assignRole($role);

    return $admin;
}

test('public password reset link workflow is disabled', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password', ['email' => 'user@example.com'])->assertNotFound();
    $this->get('/reset-password/fake-token')->assertNotFound();
    $this->post('/reset-password', [])->assertNotFound();
    $this->post('/api/v1/auth/forgot-password', ['email' => 'user@example.com'])->assertNotFound();
});

test('admin reset password emails a temporary password and requires a password change', function () {
    Mail::fake();

    $admin = passwordResetAdmin();
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);
    $previousPassword = $user->password;

    $response = $this
        ->actingAs($admin)
        ->from(route('users.index'))
        ->post(route('users.reset-password', $user));

    $response
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', "A temporary password has been emailed to {$user->email}.");

    $user->refresh();

    expect($user->must_change_password)->toBeTrue()
        ->and($user->password)->not->toBe($previousPassword)
        ->and(Hash::check('pitx@123', $user->password))->toBeFalse();

    Mail::assertSent(TemporaryPasswordMail::class, function (TemporaryPasswordMail $mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->user->is($user)
            && Hash::check($mail->temporaryPassword, $user->password);
    });
});

test('admin reset password restores the current password when email delivery fails', function () {
    $admin = passwordResetAdmin();
    $user = User::factory()->create([
        'must_change_password' => false,
    ]);
    $previousPassword = $user->password;

    Mail::shouldReceive('to')
        ->once()
        ->with($user->email)
        ->andThrow(new RuntimeException('SMTP unavailable.'));

    $response = $this
        ->actingAs($admin)
        ->from(route('users.index'))
        ->post(route('users.reset-password', $user));

    $response
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('error', 'Password reset failed. The temporary password email could not be sent.');

    $user->refresh();

    expect($user->password)->toBe($previousPassword)
        ->and($user->must_change_password)->toBeFalse();
});
