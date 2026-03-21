<?php

namespace Database\Factories;

use App\Models\Gate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gate>
 */
class GateFactory extends Factory
{
    protected $model = Gate::class;

    public function definition(): array
    {
        $staffUserId = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'it', 'terminal manager']))
            ->value('id')
            ?? User::query()->value('id');

        return [
            'gate_name' => 'Gate ' . fake()->unique()->numberBetween(1, 99),
            'status' => 'active',
            'bays' => fake()->numberBetween(6, 10),
            'created_by' => $staffUserId,
            'updated_by' => $staffUserId,
        ];
    }
}
