<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\User;
use Illuminate\Database\Seeder;

class GateSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::role(['admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $creator) {
            $this->command?->warn('No users found. Please seed users first before seeding gates.');
            return;
        }

        for ($i = 1; $i <= 6; $i++) {
            Gate::query()->updateOrCreate(
                ['gate_name' => "Gate {$i}"],
                [
                    'status' => 'active',
                    'bays' => fake()->numberBetween(6, 10),
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );
        }
    }
}
