<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;


class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        // VehicleType::factory(5)->create();

        $bus = VehicleType::updateOrCreate(
            [
                'type_name' => 'Bus',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        // $minibus = VehicleType::updateOrCreate(
        //     [
        //         'type_name' => 'Mini Bus',
        //         'is_active' => true,
        //         'created_by' => 1,
        //         'updated_by' => 1,
        //     ]
        // );

        $puv = VehicleType::updateOrCreate(
            [
                'type_name' => 'PUV',
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        // $jeep = VehicleType::updateOrCreate(
        //     [
        //         'type_name' => 'Jeep',
        //         'is_active' => true,
        //         'created_by' => 1,
        //         'updated_by' => 1,
        //     ]
        // );
    }
}
