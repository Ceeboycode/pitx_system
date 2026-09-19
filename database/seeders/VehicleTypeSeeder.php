<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->where('username', '2026-0001')->value('id');

        foreach ([
            'Coach Bus' => true,
            'Mini Bus' => true,
            'UV Express' => true,
            'Modern PUV' => true,
            'Historic Coach' => false,
        ] as $typeName => $isActive) {
            $type = VehicleType::query()
                ->whereRaw('LOWER(TRIM(type_name)) = ?', [mb_strtolower($typeName)])
                ->first();

            if ($type === null) {
                VehicleType::query()->create([
                    'type_name' => $typeName,
                    'is_active' => $isActive,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ]);

                continue;
            }

            if ($type->type_name === $typeName) {
                $type->update([
                    'is_active' => $isActive,
                    'updated_by' => $creatorId,
                ]);
            }
        }

    }
}
