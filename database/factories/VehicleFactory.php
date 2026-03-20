<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $creatorId = User::role(['operator', 'admin', 'it', 'terminal manager'])->value('id')
            ?? User::query()->value('id');

        $companyId = Company::query()->inRandomOrder()->value('id');
        $routeId = Route::query()->inRandomOrder()->value('id');

        return [
            'company_id' => $companyId,
            'route_id' => $routeId,
            'vehicle_type' => fake()->randomElement([
                'Bus',
                'Modern Jeepney',
                'Mini Bus',
                'Van',
            ]),
            'plate_number' => strtoupper(fake()->unique()->bothify('???####')),
            'body_number' => strtoupper(fake()->unique()->bothify('B-####')),
            'capacity' => fake()->numberBetween(18, 60),
            'color' => fake()->randomElement([
                'White',
                'Blue',
                'Red',
                'Silver',
                'Yellow',
            ]),
            'engine_number' => strtoupper(fake()->unique()->bothify('ENG-######')),
            'chassis_number' => strtoupper(fake()->unique()->bothify('CHS-######')),
            'make_model' => fake()->randomElement([
                'Hyundai County',
                'Isuzu NPR',
                'Hino Dutro',
                'Toyota Hiace',
                'Fuso Rosa',
            ]),
            'status' => fake()->randomElement([
                'active',
                'inactive',
                'maintenance',
            ]),
            'remarks' => fake()->optional()->sentence(),
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
            'deleted_by' => null,
            'deleted_at' => null,
        ];
    }
}
