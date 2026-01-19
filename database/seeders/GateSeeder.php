<?php

namespace Database\Seeders;

use App\Models\Gate;
use Illuminate\Database\Seeder;

class GateSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Gate::factory(3)->create();
    }
}
