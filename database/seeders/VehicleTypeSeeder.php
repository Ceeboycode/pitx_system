<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InteractsWithSeedUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VehicleTypeSeeder extends Seeder
{
    use InteractsWithSeedUser;

    public function run(): void
    {
        $userId = $this->seedUserId();
        $now = Carbon::now();

        $vehicleTypes = [
            'City Bus',
            'Mini Bus',
            'Articulated Bus',
        ];

        foreach ($vehicleTypes as $typeName) {
            DB::table('vehicle_types')->updateOrInsert(
                ['type_name' => $typeName],
                [
                    'type_name' => $typeName,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
