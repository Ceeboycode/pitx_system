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
            RolePermissionSeeder::class,

            UserSeeder::class,

            CompanySeeder::class,
            GateSeeder::class,
            VehicleTypeSeeder::class,
            RouteSeeder::class,
            RouteStopSeeder::class,
            VehicleSeeder::class,
            CompanyDocumentSeeder::class,
            VehicleDocumentSeeder::class,
            DispatchSeeder::class,

            // CrmThreadSeeder::class,
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
