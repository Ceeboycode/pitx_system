<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Demo data is only seeded in local and testing environments.');

            return;
        }

        $this->call([
            UserSeeder::class,
            VehicleTypeSeeder::class,
            DevelopmentCompanySeeder::class,
            ExternalUserSeeder::class,
            DevelopmentCompanyDocumentSeeder::class,
            DevelopmentGateSeeder::class,
            DevelopmentRouteSeeder::class,
            DevelopmentVehicleSeeder::class,
            DevelopmentVehicleDocumentSeeder::class,
            DevelopmentDispatchSeeder::class,
            DevelopmentSupportingDataSeeder::class,
        ]);
    }
}
