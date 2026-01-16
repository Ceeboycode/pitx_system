<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InteractsWithSeedUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RouteStopSeeder extends Seeder
{
    use InteractsWithSeedUser;

    public function run(): void
    {
        $userId = $this->seedUserId();
        $now = Carbon::now();

        $stops = [
            ['route' => 'North Loop', 'stop_name' => 'Terminal Hub', 'stop_order' => 1],
            ['route' => 'North Loop', 'stop_name' => 'Maple Avenue', 'stop_order' => 2],
            ['route' => 'North Loop', 'stop_name' => 'Riverside Market', 'stop_order' => 3],

            ['route' => 'Coastal Connector', 'stop_name' => 'Harbor Point', 'stop_order' => 1],
            ['route' => 'Coastal Connector', 'stop_name' => 'Shoreline Market', 'stop_order' => 2],
            ['route' => 'Coastal Connector', 'stop_name' => 'Airport Link', 'stop_order' => 3],

            ['route' => 'City Express', 'stop_name' => 'Central Station', 'stop_order' => 1],
            ['route' => 'City Express', 'stop_name' => 'Financial District', 'stop_order' => 2],
            ['route' => 'City Express', 'stop_name' => 'University Avenue', 'stop_order' => 3],
        ];

        $routes = DB::table('routes')->pluck('id', 'route_name');

        foreach ($stops as $stop) {
            $routeId = $routes[$stop['route']] ?? null;

            if (! $routeId) {
                throw new RuntimeException("Route {$stop['route']} must exist before running RouteStopSeeder.");
            }

            DB::table('route_stops')->updateOrInsert(
                [
                    'route_id' => $routeId,
                    'stop_order' => $stop['stop_order'],
                ],
                [
                    'route_id' => $routeId,
                    'stop_name' => $stop['stop_name'],
                    'stop_order' => $stop['stop_order'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
