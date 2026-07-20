<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Dispatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class DispatchSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()
            ->whereIn('company_code', ['NOR', 'SOU'])
            ->with(['vehicles.route'])
            ->get()
            ->each(function (Company $company) {
            $vehicles = $company->vehicles->where('status', 'active')->values();
            $dispatchers = User::query()
                ->where('company_id', $company->id)
                ->role('dispatcher')
                ->orderBy('username')
                ->get();

            $drivers = User::query()
                ->where('company_id', $company->id)
                ->role('driver')
                ->orderBy('username')
                ->get();

            if ($vehicles->isEmpty() || $dispatchers->isEmpty() || $drivers->isEmpty()) {
                $this->command?->warn("Skipping {$company->company_name}: active vehicles, dispatchers, and drivers are required.");
                return;
            }

            $dispatchSets = [
                [
                    'status' => 'arrived',
                    'arrived_at' => now()->subHours(3),
                    'departed_at' => null,
                    'dispatched_at' => null,
                    'pax_count' => 28,
                ],
                [
                    'status' => 'departed',
                    'arrived_at' => now()->subHours(2),
                    'departed_at' => now()->subHours(2)->addMinutes(15),
                    'dispatched_at' => null,
                    'pax_count' => 35,
                ],
                [
                    'status' => 'dispatched',
                    'arrived_at' => now()->subHour(),
                    'departed_at' => now()->subMinutes(45),
                    'dispatched_at' => now()->subMinutes(40),
                    'pax_count' => 41,
                ],
            ];

            foreach ($dispatchSets as $index => $data) {
                $vehicle = $vehicles[$index % $vehicles->count()];
                $dispatcher = $dispatchers[$index % $dispatchers->count()];
                $driver = $drivers[$index % $drivers->count()];

                Dispatch::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'vehicle_id' => $vehicle->id,
                        'status' => $data['status'],
                    ],
                    [
                        'company_id' => $company->id,
                        'gate_id' => $vehicle->route?->gate_id,
                        'plate_number' => $vehicle->plate_number,
                        'pax_count' => min($data['pax_count'], max(1, (int) ($vehicle->capacity ?? 45))),
                        'bay_number' => (string) ($index + 1),
                        'dispatcher_user_id' => $dispatcher->id,
                        'driver_user_id' => $driver->id,
                        'arrived_at' => $data['arrived_at'],
                        'departed_at' => $data['departed_at'],
                        'dispatched_at' => $data['dispatched_at'],
                        'created_by' => $dispatcher->id,
                        'updated_by' => $dispatcher->id,
                    ]
                );
            }
        });
    }
}
