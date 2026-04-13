<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleBackupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |
    | GET /vehicles/export
    |
    | Streams a CSV with human-readable vehicle data.
    |--------------------------------------------------------------------------
    */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::with([
            'company:id,company_name',
            'route:id,route_name',
        ])->orderBy('plate_number')->get();

        $filename = 'vehicles-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($vehicles) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'ID', 'Plate Number', 'Body Number', 'Vehicle Type',
                'Make / Model', 'Capacity', 'Color',
                'Engine Number', 'Chassis Number',
                'Status', 'Company', 'Route', 'Remarks', 'Created At',
            ]);

            foreach ($vehicles as $v) {
                fputcsv($out, [
                    $v->id,
                    $v->plate_number    ?? '',
                    $v->body_number     ?? '',
                    $v->vehicle_type    ?? '',
                    $v->make_model      ?? '',
                    $v->capacity        ?? '',
                    $v->color           ?? '',
                    $v->engine_number   ?? '',
                    $v->chassis_number  ?? '',
                    $v->status          ?? '',
                    $v->company?->company_name ?? '',
                    $v->route?->route_name     ?? '',
                    $v->remarks         ?? '',
                    $v->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT
    |
    | POST /vehicles/import
    |
    | Accepts a backup ZIP produced by export().
    | - Skips vehicles that already exist (matched by plate_number).
    | - Restores document files into storage/app/public/vehicle-documents/
    | - Returns a JSON summary of what was imported / skipped.
    |--------------------------------------------------------------------------
    */
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('create', Vehicle::class);

        $request->validate([
            'backup' => ['required', 'file', 'mimes:zip', 'max:102400'], // 100 MB
        ], [
            'backup.required' => 'Please select a backup ZIP file.',
            'backup.mimes'    => 'The file must be a ZIP archive.',
            'backup.max'      => 'The backup file must not exceed 100 MB.',
        ]);

        $zipFile = $request->file('backup');
        $tmpDir  = sys_get_temp_dir() . '/vbackup_import_' . Str::uuid();
        mkdir($tmpDir, 0755, true);

        $zip = new ZipArchive();

        if ($zip->open($zipFile->getRealPath()) !== true) {
            $this->cleanupTmp($tmpDir);
            return response()->json(['message' => 'Could not open the ZIP file. It may be corrupted.'], 422);
        }

        $zip->extractTo($tmpDir);
        $zip->close();

        $manifestPath = $tmpDir . '/manifest.json';

        if (! file_exists($manifestPath)) {
            $this->cleanupTmp($tmpDir);
            return response()->json(['message' => 'Invalid backup: manifest.json not found.'], 422);
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (! isset($manifest['vehicles']) || ! is_array($manifest['vehicles'])) {
            $this->cleanupTmp($tmpDir);
            return response()->json(['message' => 'Invalid backup: manifest format unrecognised.'], 422);
        }

        $summary = [
            'imported' => [],
            'skipped'  => [],
            'errors'   => [],
        ];

        $importedBy = $request->user()?->id;

        foreach ($manifest['vehicles'] as $entry) {
            $plate = $entry['plate_number'] ?? null;

            if (! $plate) {
                $summary['errors'][] = 'Skipped one entry: missing plate_number.';
                continue;
            }

            // Skip if already exists (idempotent import)
            if (Vehicle::where('plate_number', $plate)->withTrashed()->exists()) {
                $summary['skipped'][] = "{$plate} — already exists, skipped.";
                continue;
            }

            try {
                DB::transaction(function () use ($entry, $tmpDir, $importedBy, &$summary) {
                    $vehicle = Vehicle::create([
                        'plate_number'   => $entry['plate_number'],
                        'body_number'    => $entry['body_number'] ?? null,
                        'vehicle_type'   => $entry['vehicle_type'] ?? null,
                        'capacity'       => $entry['capacity'] ?? null,
                        'color'          => $entry['color'] ?? null,
                        'engine_number'  => $entry['engine_number'] ?? null,
                        'chassis_number' => $entry['chassis_number'] ?? null,
                        'make_model'     => $entry['make_model'] ?? null,
                        'status'         => $entry['status'] ?? 'for_verification',
                        'remarks'        => $entry['remarks'] ?? null,
                        'created_by'     => $importedBy,
                        'updated_by'     => $importedBy,
                    ]);

                    foreach ($entry['documents'] ?? [] as $docData) {
                        $newPath = null;

                        if (! empty($docData['archive_path'])) {
                            $srcPath = $tmpDir . '/' . $docData['archive_path'];

                            if (file_exists($srcPath)) {
                                $ext     = pathinfo($srcPath, PATHINFO_EXTENSION) ?: 'bin';
                                $newPath = "vehicle-documents/{$vehicle->id}/{$docData['document_type']}/" . Str::uuid() . '.' . $ext;

                                Storage::disk('public')->put($newPath, file_get_contents($srcPath));
                            }
                        }

                        VehicleDocument::create([
                            'vehicle_id'     => $vehicle->id,
                            'document_type'  => $docData['document_type'],
                            'file_path'      => $newPath,
                            'file_name'      => $docData['file_name'] ?? null,
                            'file_mime_type' => $docData['file_mime_type'] ?? null,
                            'file_size'      => $docData['file_size'] ?? null,
                            'issued_at'      => $docData['issued_at'] ?? null,
                            'expires_at'     => $docData['expires_at'] ?? null,
                            'status'         => $docData['status'] ?? 'pending',
                            'remarks'        => $docData['remarks'] ?? null,
                            'created_by'     => $importedBy,
                        ]);
                    }

                    $summary['imported'][] = "{$vehicle->plate_number}" . ($vehicle->body_number ? " ({$vehicle->body_number})" : '');
                });
            } catch (\Throwable $e) {
                $summary['errors'][] = "{$plate} — {$e->getMessage()}";
            }
        }

        $this->cleanupTmp($tmpDir);

        return response()->json([
            'message' => 'Import completed.',
            'summary' => $summary,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function cleanupTmp(string $dir): void
    {
        if (! is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($dir);
    }
}
