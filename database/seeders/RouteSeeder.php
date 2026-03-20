<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::role(['admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $creator) {
            $this->command?->warn('No users found. Please seed users first before seeding routes.');
            return;
        }

        $gates = Gate::query()->orderBy('id')->get();

        if ($gates->isEmpty()) {
            $this->command?->warn('No gates found. Please seed gates first before seeding routes.');
            return;
        }

        $routes = [
            ['destination_name' => 'Cavite', 'destination_lat' => 14.2814, 'destination_lng' => 120.8585, 'distance_meters' => 28500, 'duration_seconds' => 3000],
            ['destination_name' => 'Pasig', 'destination_lat' => 14.5764, 'destination_lng' => 121.0851, 'distance_meters' => 21000, 'duration_seconds' => 2700],
            ['destination_name' => 'Pasay', 'destination_lat' => 14.5378, 'destination_lng' => 121.0014, 'distance_meters' => 6500, 'duration_seconds' => 1200],
            ['destination_name' => 'Taguig', 'destination_lat' => 14.5176, 'destination_lng' => 121.0509, 'distance_meters' => 14500, 'duration_seconds' => 1800],
            ['destination_name' => 'Makati', 'destination_lat' => 14.5547, 'destination_lng' => 121.0244, 'distance_meters' => 12000, 'duration_seconds' => 1600],
            ['destination_name' => 'Manila', 'destination_lat' => 14.5995, 'destination_lng' => 120.9842, 'distance_meters' => 14000, 'duration_seconds' => 1900],
        ];

        foreach ($routes as $index => $route) {
            $gate = $gates[$index % $gates->count()];

            Route::query()->updateOrCreate(
                [
                    'route_name' => 'PITX to ' . $route['destination_name'],
                ],
                [
                    'origin_name' => 'PITX',
                    'origin_lat' => 14.5096,
                    'origin_lng' => 120.9915,
                    'destination_name' => $route['destination_name'],
                    'destination_lat' => $route['destination_lat'],
                    'destination_lng' => $route['destination_lng'],
                    'distance_meters' => $route['distance_meters'],
                    'duration_seconds' => $route['duration_seconds'],
                    'route_geometry' => null,
                    'status' => 'active',
                    'gate_id' => $gate->id,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );
        }
    }
}
