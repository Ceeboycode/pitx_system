<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // SUPER ADMIN
        $superAdmin = User::updateOrCreate(
            ['username' => 'superadmin'], // unique key
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'phone_number' => '09123456789',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
            ]
        );

        $superAdmin->syncRoles(['super-admin']); // ensures correct role

        // ADMIN
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'phone_number' => '09123456788',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
            ]
        );

        $admin->syncRoles(['admin']);

        // COMMUTER (mobile CRM user)
        $commuter = User::updateOrCreate(
            ['username' => 'commuter1'],
            [
                'name' => 'Commuter User',
                'email' => 'commuter@gmail.com',
                'phone_number' => '09123456780',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'company_id' => null,
            ]
        );

        $commuter->syncRoles(['commuter']);

        $commuter = User::updateOrCreate(
            ['username' => 'commuter2'],
            [
                'name' => 'Commuter2 User',
                'email' => 'commuter2@gmail.com',
                'phone_number' => '09222222222',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'company_id' => null,
            ]
        );

        $commuter->syncRoles(['commuter']);

        // DISPATCHERS (avoid duplicates if seed is re-run)
        // User::factory()
        //     ->count(5)
        //     ->create()
        //     ->each(function (User $user) {
        //         $user->assignRole('dispatcher');
        //     });
    }
}
