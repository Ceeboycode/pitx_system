<?php

namespace Database\Seeders;

use App\Models\Gate;
use Illuminate\Database\Seeder;

class GateSeeder extends Seeder
{
    public function run(): void
    {
        $gates = [
            ['gate_name' => 'Gate 1', 'bays' => 22, 'location' => '2nd Floor'],
            ['gate_name' => 'Gate 2', 'bays' => 18, 'location' => '2nd Floor'],
            ['gate_name' => 'Gate 3', 'bays' => 15, 'location' => '2nd Floor'],
            ['gate_name' => 'Gate 4', 'bays' => 20, 'location' => '2nd Floor'],
            ['gate_name' => 'Gate 5', 'bays' => 16, 'location' => 'Ground Floor'],
            ['gate_name' => 'Gate 6', 'bays' => 16, 'location' => 'Ground Floor'],
            ['gate_name' => 'Gate 7', 'bays' => 14, 'location' => 'Ground Floor'],
            ['gate_name' => 'Gate 10', 'bays' => 12, 'location' => 'Ground Floor'],
        ];

        foreach ($gates as $gate) {
            Gate::updateOrCreate(
                ['gate_name' => $gate['gate_name']],
                [
                    'bays' => $gate['bays'],
                    'location' => $gate['location'],
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                ],
            );
        }
    }
}
