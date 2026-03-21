<?php

namespace Database\Factories;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RouteStop>
 */
class RouteStopFactory extends Factory
{
    protected $model = RouteStop::class;

    public function definition(): array
    {
        $creatorId = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'it', 'terminal manager']))
            ->value('id')
            ?? User::query()->value('id');

        return [
            'stop_name' => fake()->streetName(),
            'stop_type' => 'stop',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(14.0, 14.9),
            'longitude' => fake()->longitude(120.8, 121.2),
            'mapbox_feature_id' => null,
            'route_id' => Route::query()->inRandomOrder()->value('id'),
            'stop_order' => 1,
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
            'deleted_at' => null,
        ];
    }
}
