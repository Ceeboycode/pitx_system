<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\CompanyUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('gives each company one operator, two drivers and two dispatchers', function (): void {
    $company = Company::factory()->create(['company_code' => 'ARS']);

    $this->seed(CompanyUserSeeder::class);

    $users = User::query()->where('company_id', $company->id)->orderBy('username')->get();

    expect($users)->toHaveCount(5)
        ->and($users->pluck('username')->all())->toBe(['ARS-0001', 'ARS-0002', 'ARS-0003', 'ARS-0004', 'ARS-0005'])
        ->and(User::role('operator')->where('company_id', $company->id)->count())->toBe(1)
        ->and(User::role('driver')->where('company_id', $company->id)->count())->toBe(2)
        ->and(User::role('dispatcher')->where('company_id', $company->id)->count())->toBe(2);

    $users->each(function (User $user): void {
        expect($user->status)->toBe('active')
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('pitx@123', $user->password))->toBeTrue();
    });
});

it('only tops up missing users and verifies existing ones when run again', function (): void {
    $company = Company::factory()->create(['company_code' => 'ARS']);
    $driver = User::factory()->external($company->id)->unverified()->create(['username' => 'ARS-0002']);
    $driver->assignRole('driver');

    $this->seed(CompanyUserSeeder::class);
    $this->seed(CompanyUserSeeder::class);

    expect(User::query()->where('company_id', $company->id)->count())->toBe(5)
        ->and(User::role('driver')->where('company_id', $company->id)->count())->toBe(2)
        ->and($driver->fresh()->email_verified_at)->not->toBeNull();
});

it('skips companies without a company code', function (): void {
    $company = Company::factory()->create(['company_code' => '']);

    $this->seed(CompanyUserSeeder::class);

    expect(User::query()->where('company_id', $company->id)->count())->toBe(0);
});
