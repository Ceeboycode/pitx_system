<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $fallbackUser = User::role(['operator', 'admin', 'terminal manager'])->first()
            ?? User::query()->first();

        if (! $fallbackUser) {
            $this->command?->warn('No users found. Please seed users first before seeding vehicle documents.');
            return;
        }

        Vehicle::query()->with('company')->get()->each(function (Vehicle $vehicle) use ($fallbackUser) {
            $operator = User::query()
                ->where('company_id', $vehicle->company_id)
                ->role('operator')
                ->first();

            $creatorId = $operator?->id ?? $fallbackUser->id;

            foreach (Vehicle::REQUIRED_DOCUMENT_TYPES as $docType) {
                $usesDates = $docType !== 'puv_identification_markings';
                $issuedAt = $usesDates ? now()->subMonths(6)->toDateString() : null;
                $expiresAt = $usesDates ? now()->addYear()->toDateString() : null;
                $fileName = Str::slug($vehicle->plate_number . '-' . $docType) . '.pdf';
                $filePath = "vehicle-documents/{$vehicle->id}/{$docType}/{$fileName}";
                $pdf = $this->samplePdf($vehicle, $docType, $issuedAt, $expiresAt);

                Storage::disk('public')->put($filePath, $pdf);

                $document = VehicleDocument::withTrashed()->updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'document_type' => $docType,
                    ],
                    [
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'file_mime_type' => 'application/pdf',
                        'file_size' => strlen($pdf),
                        'status' => 'verified',
                        'issued_at' => $issuedAt,
                        'expires_at' => $expiresAt,
                        'created_by' => $creatorId,
                        'updated_by' => $creatorId,
                    ],
                );

                if ($document->trashed()) {
                    $document->restore();
                }
            }
        });
    }

    private function samplePdf(
        Vehicle $vehicle,
        string $docType,
        ?string $issuedAt,
        ?string $expiresAt,
    ): string {
        $lines = [
            'SAMPLE / SEED DATA - NOT AN OFFICIAL DOCUMENT',
            str_replace('_', ' ', strtoupper($docType)),
            'Company: ' . $vehicle->company->company_name,
            'Plate Number: ' . $vehicle->plate_number,
            'Body Number: ' . ($vehicle->body_number ?? 'N/A'),
        ];

        if ($issuedAt !== null) {
            $lines[] = 'Issued: ' . $issuedAt;
        }

        if ($expiresAt !== null) {
            $lines[] = 'Expires: ' . $expiresAt;
        }

        $content = "BT\n/F1 16 Tf\n50 760 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -28 Td\n";
            }

            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "({$escaped}) Tj\n";
        }
        $content .= "ET\n";

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            4 => '<< /Length ' . strlen($content) . ">>\nstream\n{$content}endstream",
            5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($number = 1; $number <= 5; $number++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$number]) . "\n";
        }

        return $pdf
            . "trailer\n<< /Size 6 /Root 1 0 R >>\n"
            . "startxref\n{$xrefOffset}\n%%EOF\n";
    }
}
