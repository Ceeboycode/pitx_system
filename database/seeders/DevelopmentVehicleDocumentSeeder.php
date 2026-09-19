<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Services\Vehicle\VehicleService;
use Database\Seeders\Concerns\CreatesSeedPdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DevelopmentVehicleDocumentSeeder extends Seeder
{
    use CreatesSeedPdf;

    public function run(VehicleService $vehicleService): void
    {
        foreach ($this->scenarios() as $plateNumber => $scenario) {
            $vehicle = Vehicle::query()->where('plate_number', $plateNumber)->first();
            $creatorId = $vehicle?->company?->users()->role('operator')->value('id');

            if ($vehicle === null || $creatorId === null) {
                continue;
            }

            foreach (Vehicle::REQUIRED_DOCUMENT_TYPES as $documentType) {
                $this->seedDocument($vehicle, $documentType, $scenario, $creatorId);
            }

            $vehicleService->syncVehicleStatus($vehicle, $creatorId);
        }
    }

    /**
     * @return array<string, array{status: string, expires_at: string|null, remarks: string|null}>
     */
    private function scenarios(): array
    {
        return [
            'DEV-NOR-100' => ['status' => 'verified', 'expires_at' => now()->addYear()->toDateString(), 'remarks' => null],
            'DEV-ISS-100' => ['status' => 'invalid', 'expires_at' => now()->addYear()->toDateString(), 'remarks' => 'Insurance certificate details are unreadable.'],
            'DEV-EXP-100' => ['status' => 'expired', 'expires_at' => now()->subDay()->toDateString(), 'remarks' => 'Insurance certificate has expired.'],
        ];
    }

    /**
     * @param  array{status: string, expires_at: string|null, remarks: string|null}  $scenario
     */
    private function seedDocument(Vehicle $vehicle, string $documentType, array $scenario, int $creatorId): void
    {
        $isScenarioDocument = $documentType === Vehicle::REQUIRED_DOCUMENT_TYPES[0];
        $status = $isScenarioDocument ? $scenario['status'] : 'verified';
        $fileName = strtolower($vehicle->plate_number.'-'.$documentType).'.pdf';
        $filePath = "seed-fixtures/vehicles/{$vehicle->plate_number}/{$fileName}";

        if (! Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->put($filePath, $this->seedPdf([
                'SAMPLE DEVELOPMENT DATA — NOT AN OFFICIAL DOCUMENT',
                $vehicle->plate_number,
                $documentType,
            ]));
        }

        $document = VehicleDocument::withTrashed()->updateOrCreate(
            ['vehicle_id' => $vehicle->id, 'document_type' => $documentType],
            [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_mime_type' => 'application/pdf',
                'file_size' => Storage::disk('public')->size($filePath),
                'status' => $status,
                'issued_at' => $documentType === 'puv_identification_markings' ? null : now()->subMonths(6)->toDateString(),
                'expires_at' => $documentType === 'puv_identification_markings' ? null : ($isScenarioDocument ? $scenario['expires_at'] : now()->addYear()->toDateString()),
                'remarks' => $isScenarioDocument ? $scenario['remarks'] : null,
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
            ],
        );

        if ($document->trashed()) {
            $document->restore();
        }
    }
}
