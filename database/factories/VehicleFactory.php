<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plate_number' => strtoupper($this->faker->bothify('???-####')),
            'body_number' => strtoupper($this->faker->bothify('BD-####')),
            'vehicle_type_id' => \App\Models\VehicleType::inRandomOrder()->first()->id,
            'company_id' => \App\Models\Company::inRandomOrder()->first()->id,
            'route_id' => \App\Models\Route::inRandomOrder()->first()->id,
            'capacity' => $this->faker->numberBetween(20, 60),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
