<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InteractsWithSeedUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GateSeeder extends Seeder
{
    use InteractsWithSeedUser;

    public function run(): void
    {
        $userId = $this->seedUserId();
        $now = Carbon::now();

        $gates = [
            'North Gate',
            'Coastal Gate',
            'Central Gate',
        ];

        foreach ($gates as $gateName) {
            DB::table('gates')->updateOrInsert(
                ['gate_name' => $gateName],
                [
                    'gate_name' => $gateName,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
