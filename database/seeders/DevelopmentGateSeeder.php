<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentGateSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->where('username', '2026-0003')->value('id');

        foreach ([
            ['gate_name' => 'Development Gate A', 'status' => 'active', 'bays' => 10, 'location' => 'PITX North Wing'],
            ['gate_name' => 'Development Gate B', 'status' => 'active', 'bays' => 6, 'location' => 'PITX South Wing'],
            ['gate_name' => 'Development Gate Historical', 'status' => 'inactive', 'bays' => 4, 'location' => 'PITX Annex'],
        ] as $gate) {
            Gate::withTrashed()->updateOrCreate(
                ['gate_name' => $gate['gate_name']],
                [...$gate, 'created_by' => $creatorId, 'updated_by' => $creatorId],
            );
        }
    }
}
