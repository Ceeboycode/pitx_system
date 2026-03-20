<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VehicleDocument>
 */
class VehicleDocumentFactory extends Factory
{
    protected $model = VehicleDocument::class;

    public function definition(): array
    {
        $vehicleId = Vehicle::query()->inRandomOrder()->value('id');

        $creatorId = User::role(['operator', 'admin', 'it', 'terminal manager'])->value('id')
            ?? User::query()->value('id');

        $issuedAt = fake()->dateTimeBetween('-1 year', '-1 month');
        $expiresAt = fake()->dateTimeBetween('+1 month', '+2 years');

        return [
            'vehicle_id' => $vehicleId,
            'document_type' => fake()->randomElement([
                'or_cr',
                'cpc',
                'ltfrb_certificate',
                'insurance',
                'inspection',
            ]),
            'file_path' => 'vehicle-documents/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->slug() . '.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 3000000),
            'status' => fake()->randomElement(['pending', 'verified', 'invalid', 'expired']),
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'remarks' => fake()->optional()->sentence(),
            'created_by' => $creatorId,
            'updated_by' => $creatorId,
            'deleted_by' => null,
        ];
    }
}
