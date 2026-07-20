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
        $vehicles = [
            'NOR' => [
                [
                    'route_name' => 'PITX - Trece Martires',
                    'plate_number' => 'NPT2145',
                    'body_number' => 'NOR-B001',
                    'capacity' => 49,
                    'color' => 'White and Blue',
                    'engine_number' => 'J08E-WF-NOR001',
                    'chassis_number' => 'RN8J-NOR-0001',
                    'make_model' => 'Hino RN8J',
                ],
                [
                    'route_name' => 'PITX - Tagaytay-Mendez (Erjohn, San Agustin)',
                    'plate_number' => 'NPT3857',
                    'body_number' => 'NOR-B002',
                    'capacity' => 45,
                    'color' => 'Blue',
                    'engine_number' => '6HK1-NOR002',
                    'chassis_number' => 'LV123-NOR-0002',
                    'make_model' => 'Isuzu LV123',
                ],
                [
                    'route_name' => 'PITX - Bicol',
                    'plate_number' => 'NPT4621',
                    'body_number' => 'NOR-B003',
                    'capacity' => 47,
                    'color' => 'Silver',
                    'engine_number' => 'D6CA-NOR003',
                    'chassis_number' => 'UNV-NOR-0003',
                    'make_model' => 'Hyundai Universe',
                ],
            ],
            'SOU' => [
                [
                    'route_name' => 'PITX - Dasmariñas',
                    'plate_number' => 'SBT1208',
                    'body_number' => 'SOU-B001',
                    'capacity' => 50,
                    'color' => 'White and Red',
                    'engine_number' => 'J08E-SOU001',
                    'chassis_number' => 'RK8-SOU-0001',
                    'make_model' => 'Hino RK8JSKA',
                ],
                [
                    'route_name' => 'PITX - Naic',
                    'plate_number' => 'SBT2746',
                    'body_number' => 'SOU-B002',
                    'capacity' => 35,
                    'color' => 'Red',
                    'engine_number' => '4HK1-SOU002',
                    'chassis_number' => 'NQR75-SOU-0002',
                    'make_model' => 'Isuzu NQR75',
                ],
                [
                    'route_name' => 'PITX - Buendia (Beep)',
                    'plate_number' => 'SBT3914',
                    'body_number' => 'SOU-B003',
                    'capacity' => 29,
                    'color' => 'White',
                    'engine_number' => 'D4DD-SOU003',
                    'chassis_number' => 'CNTY-SOU-0003',
                    'make_model' => 'Hyundai County',
                ],
            ],
        ];

        foreach ($vehicles as $companyCode => $companyVehicles) {
            $company = Company::query()->where('company_code', $companyCode)->first();

            if (! $company) {
                $this->command?->warn("Skipping {$companyCode} vehicles: company not found.");
                continue;
            }

            $operator = User::query()
                ->where('company_id', $company->id)
                ->role('operator')
                ->first();

            if (! $operator) {
                $this->command?->warn("Skipping {$companyCode} vehicles: operator account not found.");
                continue;
            }

            foreach ($companyVehicles as $data) {
                $routeName = $data['route_name'];
                unset($data['route_name']);

                $route = Route::query()->where('route_name', $routeName)->first();

                if (! $route) {
                    $this->command?->warn("Skipping {$data['plate_number']}: route {$routeName} not found.");
                    continue;
                }

                Vehicle::query()->updateOrCreate(
                    ['plate_number' => $data['plate_number']],
                    [
                        ...$data,
                        'company_id' => $company->id,
                        'route_id' => $route->id,
                        'vehicle_type' => 'Bus',
                        'status' => 'active',
                        'created_by' => $operator->id,
                        'updated_by' => $operator->id,
                    ],
                );
            }
        }
    }
}
