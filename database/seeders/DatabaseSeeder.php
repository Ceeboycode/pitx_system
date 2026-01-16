<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            CompanySeeder::class,
            GateSeeder::class,
            RouteSeeder::class,
            RouteStopSeeder::class,
            VehicleTypeSeeder::class,
            VehicleSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
