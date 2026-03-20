<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::role(['operator', 'admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $creator) {
            $this->command?->warn('No users found. Please seed users first before seeding vehicles.');
            return;
        }

        $routes = Route::query()->orderBy('id')->get();

        if ($routes->isEmpty()) {
            $this->command?->warn('No routes found. Please seed routes first before seeding vehicles.');
            return;
        }

        Company::query()->get()->each(function (Company $company) use ($routes, $creator) {
            $vehicles = [
                [
                    'vehicle_type' => 'Bus',
                    'plate_number' => strtoupper(fake()->unique()->bothify('N??####')),
                    'body_number' => strtoupper(fake()->unique()->bothify('BUS-###')),
                    'capacity' => 45,
                    'color' => 'White',
                    'make_model' => 'Hyundai County',
                ],
                [
                    'vehicle_type' => 'Modern Jeepney',
                    'plate_number' => strtoupper(fake()->unique()->bothify('N??####')),
                    'body_number' => strtoupper(fake()->unique()->bothify('MJ-###')),
                    'capacity' => 30,
                    'color' => 'Blue',
                    'make_model' => 'Isuzu NPR',
                ],
                [
                    'vehicle_type' => 'Mini Bus',
                    'plate_number' => strtoupper(fake()->unique()->bothify('N??####')),
                    'body_number' => strtoupper(fake()->unique()->bothify('MB-###')),
                    'capacity' => 25,
                    'color' => 'Silver',
                    'make_model' => 'Fuso Rosa',
                ],
            ];

            foreach ($vehicles as $index => $vehicle) {
                $route = $routes[$index % $routes->count()];

                Vehicle::query()->updateOrCreate(
                    ['plate_number' => $vehicle['plate_number']],
                    [
                        'company_id' => $company->id,
                        'route_id' => $route->id,
                        'vehicle_type' => $vehicle['vehicle_type'],
                        'body_number' => $vehicle['body_number'],
                        'capacity' => $vehicle['capacity'],
                        'color' => $vehicle['color'],
                        'engine_number' => strtoupper(fake()->unique()->bothify('ENG-######')),
                        'chassis_number' => strtoupper(fake()->unique()->bothify('CHS-######')),
                        'make_model' => $vehicle['make_model'],
                        'status' => 'active',
                        'remarks' => 'Seeded vehicle for ' . $company->company_name,
                        'created_by' => $creator->id,
                        'updated_by' => $creator->id,
                    ]
                );
            }
        });
    }
}
