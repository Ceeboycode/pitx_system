<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Demo data is only seeded in local and testing environments.');

            return;
        }

        $this->call(DemoDataSeeder::class);
    }
}
