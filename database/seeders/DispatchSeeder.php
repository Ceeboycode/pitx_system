<?php

namespace Database\Seeders;

use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DispatchSeeder extends Seeder
{
    public function run(): void
    {
        $fallbackInternal = User::role(['admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $fallbackInternal) {
            $this->command?->warn('No users found. Please seed users first before seeding dispatches.');
            return;
        }

        Vehicle::query()->with(['route', 'company'])->get()->each(function (Vehicle $vehicle) use ($fallbackInternal) {
            $dispatcher = User::query()
                ->where('company_id', $vehicle->company_id)
                ->role('dispatcher')
                ->inRandomOrder()
                ->first();

            $driver = User::query()
                ->where('company_id', $vehicle->company_id)
                ->role('driver')
                ->inRandomOrder()
                ->first();

            $creator = $dispatcher ?? $fallbackInternal;

            $dispatchSets = [
                [
                    'status' => 'arrived',
                    'arrived_at' => now()->subHours(3),
                    'departed_at' => null,
                    'dispatched_at' => null,
                    'pax_count' => fake()->numberBetween(8, (int) ($vehicle->capacity ?? 45)),
                ],
                [
                    'status' => 'departed',
                    'arrived_at' => now()->subHours(2),
                    'departed_at' => now()->subHours(2)->addMinutes(15),
                    'dispatched_at' => null,
                    'pax_count' => fake()->numberBetween(10, (int) ($vehicle->capacity ?? 45)),
                ],
                [
                    'status' => 'dispatched',
                    'arrived_at' => now()->subHour(),
                    'departed_at' => now()->subMinutes(45),
                    'dispatched_at' => now()->subMinutes(40),
                    'pax_count' => fake()->numberBetween(12, (int) ($vehicle->capacity ?? 45)),
                ],
            ];

            foreach ($dispatchSets as $index => $data) {
                Dispatch::query()->updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'dispatched_at' => $data['dispatched_at'],
                        'status' => $data['status'],
                    ],
                    [
                        'company_id' => $vehicle->company_id,
                        'gate_id' => $vehicle->route?->gate_id,
                        'plate_number' => $vehicle->plate_number,
                        'pax_count' => $data['pax_count'],
                        'bay_number' => 'Bay ' . (($index % 6) + 1),
                        'remarks' => 'Seeded dispatch record',
                        'dispatcher_user_id' => $dispatcher?->id ?? $fallbackInternal->id,
                        'driver_user_id' => $driver?->id,
                        'arrived_at' => $data['arrived_at'],
                        'departed_at' => $data['departed_at'],
                        'dispatched_at' => $data['dispatched_at'],
                        'created_by' => $creator->id,
                        'updated_by' => $creator->id,
                        'deleted_by' => null,
                    ]
                );
            }
        });
    }
}
