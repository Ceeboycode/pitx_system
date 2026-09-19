<?php

namespace Database\Seeders;

use App\Enums\RouteStatus;
use App\Models\Gate;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentRouteSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->where('username', '2026-0003')->value('id');

        foreach ($this->routes() as $scenario) {
            $gateId = Gate::query()->where('gate_name', $scenario['gate_name'])->value('id');

            if ($gateId === null) {
                continue;
            }

            $route = Route::query()->updateOrCreate(
                ['route_name' => $scenario['route_name']],
                [
                    'gate_id' => $gateId,
                    'origin_name' => 'PITX',
                    'origin_lat' => 14.5096,
                    'origin_lng' => 120.9915,
                    'destination_name' => $scenario['destination'],
                    'destination_lat' => $scenario['latitude'],
                    'destination_lng' => $scenario['longitude'],
                    'distance_meters' => $scenario['distance_meters'],
                    'duration_seconds' => $scenario['duration_seconds'],
                    'route_geometry' => null,
                    'status' => $scenario['status'],
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ],
            );

            foreach (['PITX', 'Development Stop', $scenario['destination']] as $index => $stopName) {
                RouteStop::withTrashed()->updateOrCreate(
                    ['route_id' => $route->id, 'stop_order' => $index + 1],
                    [
                        'stop_name' => $stopName,
                        'stop_type' => $index === 0 ? 'origin' : ($index === 2 ? 'destination' : 'stop'),
                        'address' => $stopName.', Metro Manila',
                        'latitude' => $index === 0 ? 14.5096 : $scenario['latitude'],
                        'longitude' => $index === 0 ? 120.9915 : $scenario['longitude'],
                        'created_by' => $creatorId,
                        'updated_by' => $creatorId,
                    ],
                );
            }
        }
    }

    /**
     * @return array<int, array<string, int|float|string|RouteStatus>>
     */
    private function routes(): array
    {
        return [
            ['route_name' => 'Development PITX to Trece Martires', 'gate_name' => 'Development Gate A', 'destination' => 'Trece Martires', 'latitude' => 14.2814, 'longitude' => 120.8585, 'distance_meters' => 28500, 'duration_seconds' => 3000, 'status' => RouteStatus::Active],
            ['route_name' => 'Development PITX to Dasmariñas', 'gate_name' => 'Development Gate B', 'destination' => 'Dasmariñas', 'latitude' => 14.3294, 'longitude' => 120.9367, 'distance_meters' => 22000, 'duration_seconds' => 2700, 'status' => RouteStatus::Active],
            ['route_name' => 'Development Historical Route', 'gate_name' => 'Development Gate Historical', 'destination' => 'Historical Depot', 'latitude' => 14.4000, 'longitude' => 121.0000, 'distance_meters' => 12000, 'duration_seconds' => 1800, 'status' => RouteStatus::Inactive],
        ];
    }
}
