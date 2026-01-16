<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InteractsWithSeedUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RouteSeeder extends Seeder
{
    use InteractsWithSeedUser;

    public function run(): void
    {
        $userId = $this->seedUserId();
        $now = Carbon::now();

        $routes = [
            ['route_name' => 'North Loop', 'gate' => 'North Gate'],
            ['route_name' => 'Coastal Connector', 'gate' => 'Coastal Gate'],
            ['route_name' => 'City Express', 'gate' => 'Central Gate'],
        ];

        $gates = DB::table('gates')->pluck('id', 'gate_name');

        foreach ($routes as $route) {
            $gateId = $gates[$route['gate']] ?? null;

            if (! $gateId) {
                throw new RuntimeException("Gate {$route['gate']} must exist before running RouteSeeder.");
            }

            DB::table('routes')->updateOrInsert(
                ['route_name' => $route['route_name']],
                [
                    'route_name' => $route['route_name'],
                    'gate_id' => $gateId,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
