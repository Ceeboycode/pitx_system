<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InteractsWithSeedUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    use InteractsWithSeedUser;

    public function run(): void
    {
        $userId = $this->seedUserId();
        $now = Carbon::now();

        $companies = [
            'Metro Transit Holdings',
            'Island Transport Cooperative',
            'Northwind Express Lines',
        ];

        foreach ($companies as $companyName) {
            DB::table('companies')->updateOrInsert(
                ['company_name' => $companyName],
                [
                    'company_name' => $companyName,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
