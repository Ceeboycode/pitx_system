<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InteractsWithSeedUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VehicleSeeder extends Seeder
{
    use InteractsWithSeedUser;

    public function run(): void
    {
        $userId = $this->seedUserId();
        $now = Carbon::now();

        $vehicles = [
            [
                'plate_number' => 'NAB-1234',
                'body_number' => 'B-100',
                'type' => 'City Bus',
                'company' => 'Metro Transit Holdings',
                'route' => 'North Loop',
                'capacity' => 60,
            ],
            [
                'plate_number' => 'COA-5678',
                'body_number' => 'B-210',
                'type' => 'Mini Bus',
                'company' => 'Island Transport Cooperative',
                'route' => 'Coastal Connector',
                'capacity' => 35,
            ],
            [
                'plate_number' => 'EXP-9012',
                'body_number' => 'B-330',
                'type' => 'Articulated Bus',
                'company' => 'Northwind Express Lines',
                'route' => 'City Express',
                'capacity' => 80,
            ],
        ];

        $vehicleTypes = DB::table('vehicle_types')->pluck('id', 'type_name');
        $companies = DB::table('companies')->pluck('id', 'company_name');
        $routes = DB::table('routes')->pluck('id', 'route_name');

        foreach ($vehicles as $vehicle) {
            $typeId = $vehicleTypes[$vehicle['type']] ?? null;
            $companyId = $companies[$vehicle['company']] ?? null;
            $routeId = $routes[$vehicle['route']] ?? null;

            if (! $typeId) {
                throw new RuntimeException("Vehicle type {$vehicle['type']} must exist before running VehicleSeeder.");
            }

            if (! $companyId) {
                throw new RuntimeException("Company {$vehicle['company']} must exist before running VehicleSeeder.");
            }

            if (! $routeId) {
                throw new RuntimeException("Route {$vehicle['route']} must exist before running VehicleSeeder.");
            }

            DB::table('vehicles')->updateOrInsert(
                ['plate_number' => $vehicle['plate_number']],
                [
                    'plate_number' => $vehicle['plate_number'],
                    'body_number' => $vehicle['body_number'],
                    'vehicle_type_id' => $typeId,
                    'company_id' => $companyId,
                    'route_id' => $routeId,
                    'capacity' => $vehicle['capacity'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
