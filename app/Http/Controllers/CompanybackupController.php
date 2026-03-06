<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class CompanyBackupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |
    | GET /companies/export
    |
    | Streams a ZIP containing:
    |   manifest.json          — all companies + documents metadata
    |   files/{company_code}/{doc_type}/{filename}  — actual document files
    |--------------------------------------------------------------------------
    */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Company::class);

        // Optionally scope to specific company IDs
        $request->validate([
            'ids'   => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ]);

        $query = Company::with([
            'documents',
            'creator:id,name,email',
            'users:id,company_id,name,email,username,phone_number',
        ]);

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->input('ids'));
        }

        $companies = $query->get();

        abort_if($companies->isEmpty(), 404, 'No companies to export.');

        $tmpPath = tempnam(sys_get_temp_dir(), 'backup_') . '.zip';
        $zip     = new ZipArchive();

        abort_if(
            $zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true,
            500,
            'Could not create ZIP archive.'
        );

        $manifest = [
            'version'    => '1.0',
            'exported_at' => now()->toIso8601String(),
            'exported_by' => $request->user()?->name,
            'companies'  => [],
        ];

        foreach ($companies as $company) {
            $companyEntry = [
                'id'                                  => $company->id,
                'company_name'                        => $company->company_name,
                'company_code'                        => $company->company_code,
                'company_email'                       => $company->company_email,
                'company_phone'                       => $company->company_phone,
                'company_address'                     => $company->company_address,
                'business_type'                       => $company->business_type,
                'registration_number'                 => $company->registration_number,
                'authorized_representative_name'      => $company->authorized_representative_name,
                'authorized_representative_position'  => $company->authorized_representative_position,
                'authorized_representative_contact'   => $company->authorized_representative_contact,
                'status'                              => $company->status,
                'created_at'                          => $company->created_at?->toIso8601String(),
                'users'                               => $company->users->map(fn ($u) => [
                    'name'         => $u->name,
                    'email'        => $u->email,
                    'username'     => $u->username,
                    'phone_number' => $u->phone_number,
                ])->toArray(),
                'documents'                           => [],
            ];

            foreach ($company->documents as $doc) {
                $rawPath  = $doc->file_path;
                $diskPath = preg_replace('#^public/#', '', $rawPath ?? '');
                $ext      = pathinfo($diskPath, PATHINFO_EXTENSION) ?: 'bin';

                // Archive path inside ZIP: files/{company_code}/{doc_type}/{uuid}.{ext}
                $archivePath = "files/{$company->company_code}/{$doc->doc_type}/" . basename($diskPath);

                $fileIncluded = false;

                if ($diskPath && Storage::disk('public')->exists($diskPath)) {
                    $zip->addFromString($archivePath, Storage::disk('public')->get($diskPath));
                    $fileIncluded = true;
                }

                $companyEntry['documents'][] = [
                    'doc_type'      => $doc->doc_type,
                    'original_name' => $doc->original_name,
                    'mime_type'     => $doc->mime_type,
                    'file_size'     => $doc->file_size,
                    'issued_at'     => $doc->issued_at?->toDateString(),
                    'expires_at'    => $doc->expires_at?->toDateString(),
                    'status'        => $doc->status,
                    'remarks'       => $doc->remarks,
                    'archive_path'  => $fileIncluded ? $archivePath : null,
                ];
            }

            $manifest['companies'][] = $companyEntry;
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();

        abort_if(filesize($tmpPath) === 0, 500, 'Generated ZIP is empty.');

        $filename = 'companies-backup-' . now()->format('Y-m-d-His') . '.zip';

        return response()->streamDownload(function () use ($tmpPath) {
            $handle = fopen($tmpPath, 'rb');
            while (! feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
            @unlink($tmpPath);
        }, $filename, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT
    |
    | POST /companies/import
    |
    | Accepts a backup ZIP produced by export().
    | - Skips companies that already exist (matched by company_code).
    | - Restores document files into storage/app/public/company-documents/
    | - Returns a JSON summary of what was imported / skipped.
    |--------------------------------------------------------------------------
    */
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('create', Company::class);

        $request->validate([
            'backup' => ['required', 'file', 'mimes:zip', 'max:102400'], // 100 MB
        ], [
            'backup.required' => 'Please select a backup ZIP file.',
            'backup.mimes'    => 'The file must be a ZIP archive.',
            'backup.max'      => 'The backup file must not exceed 100 MB.',
        ]);

        $zipFile = $request->file('backup');
        $tmpDir  = sys_get_temp_dir() . '/backup_import_' . Str::uuid();
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

        if (! isset($manifest['companies']) || ! is_array($manifest['companies'])) {
            $this->cleanupTmp($tmpDir);
            return response()->json(['message' => 'Invalid backup: manifest format unrecognised.'], 422);
        }

        $summary = [
            'imported' => [],
            'skipped'  => [],
            'errors'   => [],
        ];

        $importedBy = $request->user()?->id;

        foreach ($manifest['companies'] as $entry) {
            $code = $entry['company_code'] ?? null;

            if (! $code) {
                $summary['errors'][] = 'Skipped one entry: missing company_code.';
                continue;
            }

            // Skip if already exists (idempotent import)
            if (Company::where('company_code', $code)->withTrashed()->exists()) {
                $summary['skipped'][] = "{$code} — already exists, skipped.";
                continue;
            }

            try {
                DB::transaction(function () use ($entry, $tmpDir, $importedBy, &$summary) {

                    $company = Company::create([
                        'company_name'                       => $entry['company_name'],
                        'company_code'                       => $entry['company_code'],
                        'company_email'                      => $entry['company_email'] ?? null,
                        'company_phone'                      => $entry['company_phone'] ?? null,
                        'company_address'                    => $entry['company_address'] ?? null,
                        'business_type'                      => $entry['business_type'] ?? null,
                        'registration_number'                => $entry['registration_number'] ?? null,
                        'authorized_representative_name'     => $entry['authorized_representative_name'] ?? null,
                        'authorized_representative_position' => $entry['authorized_representative_position'] ?? null,
                        'authorized_representative_contact'  => $entry['authorized_representative_contact'] ?? null,
                        'status'                             => $entry['status'] ?? 'for_verification',
                        'created_by'                         => $importedBy,
                        'updated_by'                         => $importedBy,
                    ]);

                    // Re-create users (assign temporary passwords — they must reset)
                    foreach ($entry['users'] ?? [] as $userData) {
                        if (! isset($userData['email'])) continue;
                        if (User::where('email', $userData['email'])->exists()) continue;

                        $companyCode = $company->company_code;
                        $username    = $userData['username'] ?? $this->nextUsernameForCode($companyCode);

                        $user = User::create([
                            'name'         => $userData['name'] ?? 'Imported User',
                            'email'        => $userData['email'],
                            'username'     => $username,
                            'phone_number' => $userData['phone_number'] ?? null,
                            'company_id'   => $company->id,
                            // Temporary password — user must reset via forgot-password
                            'password'     => Hash::make(Str::random(24)),
                        ]);

                        if (method_exists($user, 'assignRole')) {
                            $user->assignRole('dispatcher');
                        }
                    }

                    // Restore documents
                    foreach ($entry['documents'] ?? [] as $docData) {
                        $newPath = null;

                        if (! empty($docData['archive_path'])) {
                            $srcPath = $tmpDir . '/' . $docData['archive_path'];

                            if (file_exists($srcPath)) {
                                $ext     = pathinfo($srcPath, PATHINFO_EXTENSION) ?: 'bin';
                                $newPath = "company-documents/{$company->id}/{$docData['doc_type']}/" . Str::uuid() . '.' . $ext;

                                Storage::disk('public')->put($newPath, file_get_contents($srcPath));
                            }
                        }

                        CompanyDocument::create([
                            'company_id'    => $company->id,
                            'doc_type'      => $docData['doc_type'],
                            'file_path'     => $newPath,
                            'original_name' => $docData['original_name'] ?? null,
                            'mime_type'     => $docData['mime_type'] ?? null,
                            'file_size'     => $docData['file_size'] ?? null,
                            'issued_at'     => $docData['issued_at'] ?? null,
                            'expires_at'    => $docData['expires_at'] ?? null,
                            'status'        => $docData['status'] ?? 'pending',
                            'remarks'       => $docData['remarks'] ?? null,
                            'uploaded_by'   => $importedBy,
                        ]);
                    }

                    $summary['imported'][] = "{$company->company_code} — {$company->company_name}";
                });
            } catch (\Throwable $e) {
                $summary['errors'][] = "{$code} — {$e->getMessage()}";
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
    private function nextUsernameForCode(string $code): string
    {
        $last = DB::table('users')
            ->where('username', 'like', $code . '-%')
            ->orderByDesc('username')
            ->value('username');

        $next = 1;
        if (is_string($last) && preg_match('/-(\d{4})$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $code . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

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
