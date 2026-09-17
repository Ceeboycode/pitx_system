<?php

namespace Database\Seeders;

use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DevelopmentDispatchSeeder extends Seeder
{
    public function run(): void
    {
        $vehicle = Vehicle::query()->where('plate_number', 'DEV-NOR-100')->with('route')->first();
        $dispatcher = User::query()->where('username', 'northstar-dispatcher')->first();
        $driver = User::query()->where('username', 'northstar-driver')->first();

        if ($vehicle === null || $dispatcher === null || $driver === null) {
            return;
        }

        foreach ([
            ['status' => 'arrived', 'arrived_at' => now()->subHours(2), 'departed_at' => null, 'dispatched_at' => null, 'remarks' => 'Awaiting scheduled departure.'],
            ['status' => 'departed', 'arrived_at' => now()->subHours(4), 'departed_at' => now()->subHours(3), 'dispatched_at' => null, 'remarks' => 'Completed departure test scenario.'],
        ] as $scenario) {
            Dispatch::query()->updateOrCreate(
                ['vehicle_id' => $vehicle->id, 'status' => $scenario['status']],
                [
                    'company_id' => $vehicle->company_id,
                    'gate_id' => $vehicle->route?->gate_id,
                    'plate_number' => $vehicle->plate_number,
                    'pax_count' => 30,
                    'bay_number' => $scenario['status'] === 'arrived' ? 'A-01' : 'A-02',
                    'remarks' => $scenario['remarks'],
                    'dispatcher_user_id' => $dispatcher->id,
                    'driver_user_id' => $driver->id,
                    'arrived_at' => $scenario['arrived_at'],
                    'departed_at' => $scenario['departed_at'],
                    'dispatched_at' => $scenario['dispatched_at'],
                    'created_by' => $dispatcher->id,
                    'updated_by' => $dispatcher->id,
                ],
            );
        }
    }
}
