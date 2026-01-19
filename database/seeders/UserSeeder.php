<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SUPER ADMIN
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('admin123'),
        ]);

        $superAdmin->assignRole('super-admin');

        // ADMIN
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
        ]);

        $admin->assignRole('admin');

        // DISPATCHERS
        User::factory(5)->create()->each(function ($user) {
            $user->assignRole('dispatcher');
        });
    }
}
