<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Support\Collection;

class CompanyStatusService
{
    public function syncCompanyStatus(Company $company): void
    {
        $requiredTypes = $this->requiredDocTypes($company->business_type);

        $allDocs = CompanyDocument::query()
            ->where('company_id', $company->id)
            ->get();

        $requiredDocs = $allDocs
            ->whereIn('doc_type', $requiredTypes)
            ->keyBy('doc_type');

        $allUploaded = count(array_diff($requiredTypes, $requiredDocs->keys()->all())) === 0;

        if (! $allUploaded) {
            $company->updateQuietly(['status' => 'draft']);

            return;
        }

        // If ANY document (required or supporting) is invalid or expired -> needs_revision
        if ($allDocs->contains(fn (CompanyDocument $doc): bool => in_array($doc->status, ['invalid', 'expired'], true))) {
            $company->updateQuietly(['status' => 'needs_revision']);

            return;
        }

        // If all required documents are verified and no document is invalid/expired/pending
        if (
            $requiredDocs->every(fn (CompanyDocument $doc): bool => $doc->status === 'verified')
            && ! $allDocs->contains(fn (CompanyDocument $doc): bool => $doc->status === 'pending')
        ) {
            $company->updateQuietly(['status' => 'verified']);

            return;
        }

        $company->updateQuietly(['status' => 'for_verification']);
    }

    public function markExpiredDocumentsAndSync(?Collection $companies = null): int
    {
        $now = now()->toDateString();

        $query = CompanyDocument::query()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $now)
            ->whereNotIn('status', ['expired']);

        if ($companies) {
            $query->whereIn('company_id', $companies->pluck('id'));
        }

        $affectedCompanyIds = $query->pluck('company_id')->unique()->values();

        if ($affectedCompanyIds->isEmpty()) {
            return 0;
        }

        CompanyDocument::query()
            ->whereIn('company_id', $affectedCompanyIds)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', $now)
            ->where('status', '!=', 'expired')
            ->update([
                'status' => 'expired',
                'verified_by' => null,
                'verified_at' => null,
            ]);

        Company::query()
            ->whereIn('id', $affectedCompanyIds)
            ->each(fn (Company $company) => $this->syncCompanyStatus($company));

        return $affectedCompanyIds->count();
    }

    public function requiredDocTypes(?string $businessType): array
    {
        $common = ['MAYORS_PERMIT', 'BIR_2303'];

        return match ($businessType) {
            'corporate' => [...$common, 'SEC_CERT'],
            'sole_proprietorship' => [...$common, 'DTI_CERT'],
            default => $common,
        };
    }
}
