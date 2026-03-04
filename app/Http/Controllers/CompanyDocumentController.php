<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyDocumentRejectRequest;
use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CompanyDocumentController extends Controller
{
    public function download(Company $company, CompanyDocument $document)
    {
        Gate::authorize('view', $company);

        if ($document->company_id !== $company->id) {
            abort(404);
        }

        $path = $this->normalizePath($document->file_path);

        if ($path === null) {
            abort(404, 'No file path saved.');
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404, "File not found on disk: {$path}");
        }

        $filename = $document->original_name ?: basename($path);

        return $disk->download($path, $filename);
    }

    public function verify(Request $request, Company $company, CompanyDocument $document)
    {
        Gate::authorize('update', $company);

        if ($document->company_id !== $company->id) {
            abort(404);
        }

        $userId = $request->user()?->id;
        abort_if(! $userId, 403);

        $document->update([
            'status' => 'verified',
            'remarks' => null,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Document verified successfully.');
    }

    //  NEW: Unverify (back to pending)
    public function unverify(Request $request, Company $company, CompanyDocument $document)
    {
        Gate::authorize('update', $company);

        if ($document->company_id !== $company->id) {
            abort(404);
        }

        $document->update([
            'status' => 'pending',
            'remarks' => null,      // remove this line if you want to keep remarks
            'verified_by' => null,
            'verified_at' => null,
        ]);

        return redirect()->back()->with('success', 'Document set back to pending.');
    }

    public function reject(CompanyDocumentRejectRequest $request, Company $company, CompanyDocument $document)
    {
        Gate::authorize('update', $company);

        if ($document->company_id !== $company->id) {
            abort(404);
        }

        $document->update([
            'status' => 'invalid',
            'remarks' => $request->validated()['remarks'],
            'verified_by' => null,
            'verified_at' => null,
        ]);

      return redirect()->back()->with('success', 'Document rejected successfully.');
    }

    public function destroy(Request $request, Company $company, CompanyDocument $document)
    {
        Gate::authorize('delete', $company); // or 'update' if you prefer

        if ($document->company_id !== $company->id) {
            abort(404);
        }

        $path = $this->normalizePath($document->file_path);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $document->delete();

        return redirect()->back()->with('success', 'Document deleted successfully.');
    }



    private function normalizePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_replace('#^public/#', '', $path);
    }
}
