<?php

namespace Database\Seeders;

use App\Models\Gate;
use Illuminate\Database\Seeder;

class GateSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gate::factory(3)->create();
        $gate1 = Gate::updateOrCreate(
            [
                'gate_name' => 'Gate 1',
                'bays' => '22',
                'status' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $gate2 = Gate::updateOrCreate(
            [
                'gate_name' => 'Gate 2',
                'bays' => '18',
                'status' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $gate3 = Gate::updateOrCreate(
            [
                'gate_name' => 'Gate 3',
                'bays' => '15',
                'status' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $gate4 = Gate::updateOrCreate(
            [
                'gate_name' => 'Gate 4',
                'bays' => '20',
                'status' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

    }
}
