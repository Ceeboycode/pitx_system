<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehicleType>
 */
class VehicleTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // return [
        //     'type_name' => $this->faker->unique()->word . ' Bus',
        //     'is_active' => true,
        //     'created_by' => 1,
        //     'updated_by' => 1,
        // ];
    }
}
