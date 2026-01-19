<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Route;
use App\Models\RouteStop;

class RouteStopFactory extends Factory
{
    protected $model = RouteStop::class;

    public function definition(): array
    {
        return [
            'stop_name' => $this->faker->streetName(),
            'route_id' => Route::inRandomOrder()->first()->id,
            'stop_order' => 1, // will be overridden in seeder
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
