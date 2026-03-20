<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();

        // INTERNAL USERS
        $superAdmin = User::query()->updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'phone_number' => '09123456789',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'phone_number' => '09123456788',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $admin->syncRoles(['admin']);

        $it = User::query()->updateOrCreate(
            ['username' => 'ituser'],
            [
                'name' => 'IT User',
                'email' => 'it@gmail.com',
                'phone_number' => '09123456787',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $it->syncRoles(['it']);

        $terminalManager = User::query()->updateOrCreate(
            ['username' => 'terminalmanager'],
            [
                'name' => 'Terminal Manager',
                'email' => 'terminalmanager@gmail.com',
                'phone_number' => '09123456786',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $terminalManager->syncRoles(['terminal manager']);

        // EXTERNAL USERS
        if ($company) {
            $operator = User::query()->updateOrCreate(
                ['username' => 'operator1'],
                [
                    'name' => 'Operator User',
                    'email' => 'operator@gmail.com',
                    'phone_number' => '09123456785',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => Hash::make('admin123'),
                    'must_change_password' => false,
                    'company_id' => $company->id,
                ]
            );
            $operator->syncRoles(['operator']);

            $dispatcher = User::query()->updateOrCreate(
                ['username' => 'dispatcher1'],
                [
                    'name' => 'Dispatcher User',
                    'email' => 'dispatcher@gmail.com',
                    'phone_number' => '09123456784',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => Hash::make('admin123'),
                    'must_change_password' => false,
                    'company_id' => $company->id,
                ]
            );
            $dispatcher->syncRoles(['dispatcher']);

            $driver = User::query()->updateOrCreate(
                ['username' => 'driver1'],
                [
                    'name' => 'Driver User',
                    'email' => 'driver@gmail.com',
                    'phone_number' => '09123456783',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => Hash::make('admin123'),
                    'must_change_password' => false,
                    'company_id' => $company->id,
                ]
            );
            $driver->syncRoles(['driver']);

            User::factory()
                ->count(3)
                ->external($company->id)
                ->create()
                ->each(fn (User $user) => $user->syncRoles(['operator']));

            User::factory()
                ->count(5)
                ->external($company->id)
                ->create()
                ->each(fn (User $user) => $user->syncRoles(['dispatcher']));

            User::factory()
                ->count(8)
                ->external($company->id)
                ->create()
                ->each(fn (User $user) => $user->syncRoles(['driver']));
        }
    }
}
