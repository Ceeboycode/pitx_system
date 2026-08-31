<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
    Http::fake([
        'https://api.pwnedpasswords.com/range/*' => Http::response('', 200),
    ]);
});

it('requires company registration passwords to be at least twelve characters', function (): void {
    $response = $this->from(route('company-registration.show'))
        ->post(route('company-registration.storeStep1'), [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@gmail.com',
            'phone' => '09171234567',
            'password' => 'shortpass',
            'password_confirmation' => 'shortpass',
        ]);

    $response
        ->assertRedirect(route('company-registration.show'))
        ->assertSessionHasErrors([
            'password' => 'Password must be at least 12 characters.',
        ]);
});

it('stores a hash instead of the raw company registration password', function (): void {
    $password = 'a-unique-long-passphrase-2026';

    $response = $this->post(route('company-registration.storeStep1'), [
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@gmail.com',
        'phone' => '09171234567',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $storedPasswordHash = session('registration.step1.password_hash');

    expect($storedPasswordHash)
        ->toBeString()
        ->not->toBe($password)
        ->and(Hash::check($password, $storedPasswordHash))->toBeTrue();
});

it('normalizes Philippine phone number formats to the local format', function (string $phone): void {
    $password = 'a-unique-long-passphrase-2026';

    $this->post(route('company-registration.storeStep1'), [
        'name' => 'Juan Dela Cruz',
        'email' => 'juan-'.md5($phone).'@gmail.com',
        'phone' => $phone,
        'password' => $password,
        'password_confirmation' => $password,
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect(session('registration.step1.phone'))->toBe('09171234567');
})->with([
    'local' => '09171234567',
    'international without plus' => '639171234567',
    'international with plus' => '+639171234567',
]);
