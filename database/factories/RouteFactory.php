<?php

namespace Database\Factories;

use App\Models\Gate;
use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Route>
 */
class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        $destinations = [
            ['name' => 'Cavite', 'lat' => 14.2814, 'lng' => 120.8585, 'distance' => 28500, 'duration' => 3000],
            ['name' => 'Pasig', 'lat' => 14.5764, 'lng' => 121.0851, 'distance' => 21000, 'duration' => 2700],
            ['name' => 'Pasay', 'lat' => 14.5378, 'lng' => 121.0014, 'distance' => 6500, 'duration' => 1200],
            ['name' => 'Taguig', 'lat' => 14.5176, 'lng' => 121.0509, 'distance' => 14500, 'duration' => 1800],
            ['name' => 'Makati', 'lat' => 14.5547, 'lng' => 121.0244, 'distance' => 12000, 'duration' => 1600],
            ['name' => 'Manila', 'lat' => 14.5995, 'lng' => 120.9842, 'distance' => 14000, 'duration' => 1900],
        ];

        $destination = fake()->randomElement($destinations);

        $creatorId = User::role(['admin', 'it', 'terminal manager'])->value('id')
            ?? User::query()->value('id');

        $gateId = Gate::query()->inRandomOrder()->value('id');

        return [
            'route_name' => 'PITX to ' . $destination['name'],
            'origin_name' => 'PITX',
            'origin_lat' => 14.5096,
            'origin_lng' => 120.9915,
            'destination_name' => $destination['name'],
            'destination_lat' => $destination['lat'],
            'destination_lng' => $destination['lng'],
            'distance_meters' => $destination['distance'],
            'duration_seconds' => $destination['duration'],
            'route_geometry' => null,
            'status' => 'active',
            'gate_id' => $gateId,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
            'deleted_at' => null,
        ];
    }
}
