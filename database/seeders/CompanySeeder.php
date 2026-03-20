<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::role(['admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $creator) {
            $this->command?->warn('No users found. Please seed users first before seeding companies.');
            return;
        }

        Company::factory()
            ->count(5)
            ->state([
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ])
            ->create();
    }
}
