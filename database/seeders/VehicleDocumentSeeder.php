<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Database\Seeder;

class VehicleDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $fallbackUser = User::role(['operator', 'admin', 'it', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $fallbackUser) {
            $this->command?->warn('No users found. Please seed users first before seeding vehicle documents.');
            return;
        }

        $requiredDocs = [
            'or_cr',
            'cpc',
            'ltfrb_certificate',
            'insurance',
            'inspection',
        ];

        Vehicle::query()->with('company')->get()->each(function (Vehicle $vehicle) use ($requiredDocs, $fallbackUser) {
            $operator = User::query()
                ->where('company_id', $vehicle->company_id)
                ->role('operator')
                ->first();

            $creatorId = $operator?->id ?? $fallbackUser->id;

            foreach ($requiredDocs as $docType) {
                $isVerified = in_array($vehicle->status, ['active', 'inactive'], true);

                VehicleDocument::query()->updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'document_type' => $docType,
                    ],
                    [
                        'file_path' => "vehicle-documents/{$vehicle->id}/{$docType}.pdf",
                        'file_name' => "{$docType}.pdf",
                        'file_mime_type' => 'application/pdf',
                        'file_size' => fake()->numberBetween(100000, 3000000),
                        'status' => $isVerified ? 'verified' : 'pending',
                        'issued_at' => now()->subMonths(rand(1, 12))->toDateString(),
                        'expires_at' => now()->addMonths(rand(3, 24))->toDateString(),
                        'remarks' => null,
                        'created_by' => $creatorId,
                        'updated_by' => $creatorId,
                        'deleted_by' => null,
                    ]
                );
            }
        });
    }
}
