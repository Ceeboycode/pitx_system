<?php

namespace App\Services\Vehicle;

use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Support\Collection;

class VehicleService
{
    private const DOC_LABELS = [
        'insurance_certificate' => 'Insurance Certificate',
        'cpc' => 'Certificate of Public Convenience (CPC)',
        'official_receipt' => 'Official Receipt (OR)',
        'certificate_of_registration' => 'Certificate of Registration (CR)',
        'puv_identification_markings' => 'PUV Identification Markings',
    ];

    public function deleteVehicle(Vehicle $vehicle, int $userId): bool
    {
        $vehicle->deleted_by = $userId;
        $vehicle->save();

        return $vehicle->delete();
    }

    public function restoreVehicle(Vehicle $vehicle): bool
    {
        return $vehicle->restore();
    }

    public function forceDeleteVehicle(Vehicle $vehicle): bool
    {
        return $vehicle->forceDelete();
    }

    public function verifyDocument(Vehicle $vehicle, VehicleDocument $document, int $userId): VehicleDocument
    {
        $document->update([
            'status' => 'verified',
            'remarks' => null,
            'updated_by' => $userId,
        ]);

        $this->syncVehicleStatus($vehicle, $userId);

        return $document->refresh();
    }

    public function invalidateDocument(
        Vehicle $vehicle,
        VehicleDocument $document,
        string $remarks,
        int $userId,
    ): VehicleDocument {
        $document->update([
            'status' => 'invalid',
            'remarks' => $remarks,
            'updated_by' => $userId,
        ]);

        $this->syncVehicleStatus($vehicle, $userId);

        return $document->refresh();
    }

    public function unverifyDocument(Vehicle $vehicle, VehicleDocument $document, int $userId): VehicleDocument
    {
        $document->update([
            'status' => 'pending',
            'updated_by' => $userId,
        ]);

        $this->syncVehicleStatus($vehicle, $userId);

        return $document->refresh();
    }

    public function syncVehicleStatus(Vehicle $vehicle, ?int $userId = null): Vehicle
    {
        $requiredTypes = Vehicle::REQUIRED_DOCUMENT_TYPES;

        $documents = $vehicle->documents()
            ->whereIn('document_type', $requiredTypes)
            ->get()
            ->keyBy('document_type');

        // Auto-mark expired documents
        $documents->each(function ($doc) {
            if ($doc->expires_at && $doc->expires_at->isPast() && $doc->status !== 'expired') {
                $doc->update(['status' => 'expired']);
            }
        });

        $nextStatus = 'draft';
        $vehicleRemarks = null;

        $expiredDocs = $documents
            ->filter(fn ($doc) => $doc->expires_at && $doc->expires_at->isPast())
            ->keys()
            ->values();

        if ($documents->isEmpty()) {
            $nextStatus = 'draft';
        } elseif ($expiredDocs->isNotEmpty()) {
            $nextStatus = 'pending';
            $vehicleRemarks = $this->expiredDocumentsRemark($expiredDocs);
        } else {
            $missingRequiredDocument = collect($requiredTypes)
                ->contains(fn ($type) => ! $documents->has($type));

            $statuses = collect($requiredTypes)
                ->filter(fn ($type) => $documents->has($type))
                ->map(fn ($type) => $documents[$type]->status);

            if ($statuses->contains('invalid')) {
                $nextStatus = 'needs_revision';

                $firstInvalid = collect($requiredTypes)
                    ->filter(fn ($type) => $documents->has($type))
                    ->map(fn ($type) => $documents[$type])
                    ->firstWhere('status', 'invalid');

                $vehicleRemarks = $firstInvalid?->remarks;
            } elseif (
                ! $missingRequiredDocument &&
                $statuses->count() === count($requiredTypes) &&
                $statuses->every(fn ($status) => $status === 'verified')
            ) {
                $nextStatus = 'active';
            } else {
                $nextStatus = 'for_verification';
            }
        }

        $payload = [
            'status' => $nextStatus,
            'remarks' => $vehicleRemarks,
        ];

        if ($userId) {
            $payload['updated_by'] = $userId;
        }

        $vehicle->update($payload);

        return $vehicle->refresh();
    }

    public function markExpiredDocumentsAndSync(?Collection $vehicles = null): int
    {
        $today = now()->toDateString();

        $query = VehicleDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $today)
            ->where('status', '!=', 'expired');

        if ($vehicles !== null) {
            $query->whereIn('vehicle_id', $vehicles->pluck('id'));
        }

        $affectedVehicleIds = $query
            ->pluck('vehicle_id')
            ->unique()
            ->values();

        if ($affectedVehicleIds->isEmpty()) {
            return 0;
        }

        $updatedDocuments = VehicleDocument::query()
            ->whereIn('vehicle_id', $affectedVehicleIds)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $today)
            ->where('status', '!=', 'expired')
            ->update([
                'status' => 'expired',
            ]);

        Vehicle::query()
            ->whereIn('id', $affectedVehicleIds)
            ->each(fn (Vehicle $vehicle) => $this->syncVehicleStatus($vehicle));

        return $updatedDocuments;
    }

    private function expiredDocumentsRemark(Collection $expiredTypes): string
    {
        $labels = $expiredTypes
            ->map(fn ($type) => $this->documentTypeLabel((string) $type))
            ->unique()
            ->implode(', ');

        return 'Pending due to expired documents: ' . $labels;
    }

    private function documentTypeLabel(string $type): string
    {
        return self::DOC_LABELS[$type] ?? strtoupper($type);
    }
}
