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
        
        $superAdmin = User::query()->updateOrCreate(
            ['username' => '2026-0001'],
            [
                'name' => 'Cedric Heyrosa',
                'email' => 'cedric_heyrosa@gmail.com',
                'phone_number' => '+639226789012',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $superAdmin->syncRoles(['super-admin']);

        // ADMIN ACCT FOR DEVELOPMENT, WAG TATANGGALIN ======================================
        // $admin = User::query()->updateOrCreate(
        //     ['username' => 'admin'],
        //     [
        //         'name' => 'Admin User',
        //         'email' => 'admin@gmail.com',
        //         'phone_number' => '+639123456788',
        //         'status' => 'active',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('admin123'),
        //         'must_change_password' => false,
        //         'company_id' => null,
        //     ]
        // );
        // $admin->syncRoles(['admin']);

        $admin1 = User::query()->updateOrCreate(
            ['username' => '2026-0002'],
            [
                'name' => 'Pat Vicuna',
                'email' => 'pat_vicuna@gmail.com',
                'phone_number' => '+639237890123',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $admin1->syncRoles(['admin']);

        // TERMINAL MANAGER ACCT FOR DEVELOPMENT, WAG TATANGGALIN ======================================
        // $terminalManager = User::query()->updateOrCreate(
        //     ['username' => 'terminalmanager'],
        //     [
        //         'name' => 'Terminal Manager',
        //         'email' => 'terminalmanager@gmail.com',
        //         'phone_number' => '09123456786',
        //         'status' => 'active',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('admin123'),
        //         'must_change_password' => false,
        //         'company_id' => null,
        //     ]
        // );
        // $terminalManager->syncRoles(['terminal manager']);

        $terminalManager1 = User::query()->updateOrCreate(
            ['username' => 'johnc_01'], //palitan?
            [
                'name' => 'John Carter',
                'email' => 'john.carter@gmail.com',
                'phone_number' => '+639171234567',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $terminalManager1->syncRoles(['terminal manager']);

        $terminalManager2 = User::query()->updateOrCreate(
            ['username' => 'marial_22'],
            [
                'name' => 'Maria Lopez',
                'email' => 'maria.lopez@gmail.com',
                'phone_number' => '+639182345678',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $terminalManager2->syncRoles(['terminal manager']);

        $terminalManager3 = User::query()->updateOrCreate(
            ['username' => 'danreyes'],
            [
                'name' => 'Daniel Reyes',
                'email' => 'daniel.reyes@gmail.com',
                'phone_number' => '+639193456789',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $terminalManager3->syncRoles(['terminal manager']);

        $terminalManager4 = User::query()->updateOrCreate(
            ['username' => 'angelac_dev'],
            [
                'name' => 'Angela Cruz',
                'email' => 'angela.cruz@gmail.com',
                'phone_number' => '+639204567890',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $terminalManager4->syncRoles(['terminal manager']);

        $terminalManager5 = User::query()->updateOrCreate(
            ['username' => 'kevins99'],
            [
                'name' => 'Kevin Santos',
                'email' => 'kevin.santos@gmail.com',
                'phone_number' => '+639215678901',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $terminalManager5->syncRoles(['terminal manager']);

        $commuter1 = User::query()->updateOrCreate(
            ['username' => 'janrey_u'],
            [
                'name' => 'Jan Ulopani',
                'email' => 'jan_ulopani@gmail.com',
                'phone_number' => '+639248901234',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'must_change_password' => false,
                'company_id' => null,
            ]
        );
        $commuter1->syncRoles(['commuter']);

    }
}
