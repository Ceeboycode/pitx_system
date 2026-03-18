<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            GateSeeder::class,
            // CompanySeeder::class,
            // RouteSeeder::class,
            // RouteStopSeeder::class,
            VehicleTypeSeeder::class,
            // VehicleSeeder::class,
            // CrmThreadSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
