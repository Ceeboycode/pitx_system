<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;


class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        VehicleType::factory(5)->create();
    }
}
