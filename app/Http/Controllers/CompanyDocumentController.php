<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyDocumentRejectRequest;
use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class CompanyDocumentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Download single file
    |--------------------------------------------------------------------------
    */
    public function download(Company $company, CompanyDocument $document): mixed
    {
        Gate::authorize('view', $company);
        $this->assertBelongs($document, $company);

        $path = $this->normalizePath($document->file_path);

        abort_if($path === null, 404, 'No file path saved.');
        abort_unless(Storage::disk('public')->exists($path), 404, "File not found: {$path}");

        return Storage::disk('public')->download(
            $path,
            $document->original_name ?: basename($path)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Download VERIFIED docs only — streams a ZIP
    |
    | POST /companies/{company}/documents/download-bulk
    |--------------------------------------------------------------------------
    */
    public function downloadBulk(Request $request, Company $company): StreamedResponse
    {
        Gate::authorize('view', $company);

        // Only verified docs for this company
        $documents = CompanyDocument::query()
            ->where('company_id', $company->id)
            ->where('status', 'verified')
            ->get();

        abort_if($documents->isEmpty(), 404, 'No verified documents found.');

        $tmpPath = tempnam(sys_get_temp_dir(), 'docs_') . '.zip';

        $zip = new ZipArchive();
        abort_if(
            $zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true,
            500,
            'Could not create ZIP.'
        );

        $usedNames = [];

        foreach ($documents as $doc) {
            $path = $this->normalizePath($doc->file_path);

            if (! $path || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $filename = $doc->original_name ?: basename($path);
            $filename = $this->deduplicateFilename($filename, $usedNames);
            $usedNames[] = $filename;

            $zip->addFromString($filename, Storage::disk('public')->get($path));
        }

        $zip->close();

        abort_if(filesize($tmpPath) === 0, 404, 'No readable verified files found.');

        $zipName = $company->company_code
            ? "{$company->company_code}-verified-documents.zip"
            : "company-{$company->id}-verified-documents.zip";

        return response()->streamDownload(function () use ($tmpPath) {
            $handle = fopen($tmpPath, 'rb');
            while (! feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
            @unlink($tmpPath);
        }, $zipName, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Verify
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request, Company $company, CompanyDocument $document): RedirectResponse
    {
        Gate::authorize('update', $company);
        $this->assertBelongs($document, $company);
        abort_if(! $request->user()?->id, 403);

        $document->update([
            'status'      => 'verified',
            'remarks'     => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $this->syncCompanyStatus($company);

        return back()->with('success', 'Document verified successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Unverify — resets back to pending
    |--------------------------------------------------------------------------
    */
    public function unverify(Company $company, CompanyDocument $document): RedirectResponse
    {
        Gate::authorize('update', $company);
        $this->assertBelongs($document, $company);

        $document->update([
            'status'      => 'pending',
            'remarks'     => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->syncCompanyStatus($company);

        return back()->with('success', 'Document set back to pending.');
    }

    /*
    |--------------------------------------------------------------------------
    | Reject / Invalidate
    |--------------------------------------------------------------------------
    */
    public function reject(
        CompanyDocumentRejectRequest $request,
        Company $company,
        CompanyDocument $document,
    ): RedirectResponse {
        Gate::authorize('update', $company);
        $this->assertBelongs($document, $company);

        $document->update([
            'status'      => 'invalid',
            'remarks'     => $request->validated()['remarks'],
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->syncCompanyStatus($company);

        return back()->with('success', 'Document marked as invalid.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */
    public function destroy(Company $company, CompanyDocument $document): RedirectResponse
    {
        Gate::authorize('delete', $company);
        $this->assertBelongs($document, $company);

        $path = $this->normalizePath($document->file_path);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $document->delete();

        $this->syncCompanyStatus($company->fresh());

        return back()->with('success', 'Document deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Sync company status based on document states
    |--------------------------------------------------------------------------
    */
    private function syncCompanyStatus(Company $company): void
    {
        $requiredTypes = $this->requiredDocTypes($company->business_type);

        $docs = CompanyDocument::where('company_id', $company->id)
            ->whereIn('doc_type', $requiredTypes)
            ->get()
            ->keyBy('doc_type');

        $allUploaded = count(array_diff($requiredTypes, $docs->keys()->all())) === 0;

        if (! $allUploaded) {
            $company->updateQuietly(['status' => 'draft']);
            return;
        }

        $statuses = $docs->pluck('status');

        if ($statuses->contains('invalid')) {
            $company->updateQuietly(['status' => 'needs_revision']);
            return;
        }

        if ($statuses->every(fn ($s) => $s === 'verified')) {
            $company->updateQuietly(['status' => 'verified']);
            return;
        }

        $company->updateQuietly(['status' => 'for_verification']);
    }

    private function requiredDocTypes(?string $businessType): array
    {
        $common = ['MAYORS_PERMIT', 'BIR_2303'];

        return match ($businessType) {
            'corporate'           => [...$common, 'AUTHORIZATION_LETTER', 'SEC_CERT'],
            'sole_proprietorship' => [...$common, 'DTI_CERT'],
            default               => $common,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function assertBelongs(CompanyDocument $document, Company $company): void
    {
        abort_if($document->company_id !== $company->id, 404);
    }

    private function normalizePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_replace('#^public/#', '', $path);
    }

    private function deduplicateFilename(string $filename, array $usedNames): string
    {
        if (! in_array($filename, $usedNames, true)) {
            return $filename;
        }

        $ext     = pathinfo($filename, PATHINFO_EXTENSION);
        $base    = pathinfo($filename, PATHINFO_FILENAME);
        $counter = 1;

        do {
            $candidate = $ext ? "{$base} ({$counter}).{$ext}" : "{$base} ({$counter})";
            $counter++;
        } while (in_array($candidate, $usedNames, true));

        return $candidate;
    }
}
