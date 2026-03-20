<?php

namespace Database\Factories;

use App\Models\Dispatch;
use App\Models\Gate;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispatch>
 */
class DispatchFactory extends Factory
{
    protected $model = Dispatch::class;

    public function definition(): array
    {
        $vehicle = Vehicle::query()->with(['route', 'company'])->inRandomOrder()->first();

        $companyId = $vehicle?->company_id;
        $gateId = $vehicle?->route?->gate_id ?? Gate::query()->inRandomOrder()->value('id');

        $dispatcherId = User::query()
            ->where('company_id', $companyId)
            ->role('dispatcher')
            ->value('id')
            ?? User::role(['admin', 'it', 'terminal manager'])->value('id')
            ?? User::query()->value('id');

        $driverId = User::query()
            ->where('company_id', $companyId)
            ->role('driver')
            ->value('id');

        $createdBy = $dispatcherId;

        $status = fake()->randomElement([
            'pending',
            'arrived',
            'departed',
            'dispatched',
        ]);

        $arrivedAt = null;
        $departedAt = null;
        $dispatchedAt = null;

        if (in_array($status, ['arrived', 'departed', 'dispatched'], true)) {
            $arrivedAt = now()->subMinutes(fake()->numberBetween(20, 90));
        }

        if (in_array($status, ['departed', 'dispatched'], true)) {
            $departedAt = now()->subMinutes(fake()->numberBetween(5, 19));
        }

        if ($status === 'dispatched') {
            $dispatchedAt = now()->subMinutes(fake()->numberBetween(1, 4));
        }

        return [
            'company_id' => $companyId,
            'vehicle_id' => $vehicle?->id,
            'gate_id' => $gateId,
            'plate_number' => $vehicle?->plate_number,
            'pax_count' => fake()->numberBetween(0, (int) ($vehicle?->capacity ?? 45)),
            'bay_number' => 'Bay ' . fake()->numberBetween(1, 10),
            'remarks' => fake()->optional()->sentence(),
            'dispatcher_user_id' => $dispatcherId,
            'driver_user_id' => $driverId,
            'arrived_at' => $arrivedAt,
            'departed_at' => $departedAt,
            'dispatched_at' => $dispatchedAt,
            'status' => $status,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'deleted_by' => null,
            'deleted_at' => null,
        ];
    }
}
