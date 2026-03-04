<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    public function createCompany(array $data, int $userId): Company
    {
        $companyName = $data['company_name'];

        $data['company_code'] = $this->generateCompanyCode($companyName);
        $data['created_by'] = $userId;
        $data['status'] = $data['status'] ?? 'draft';

        return Company::create($data);
    }

    public function createCompanyWithDocuments(array $data, Request $request, int $userId): Company
    {
        return DB::transaction(function () use ($data, $request, $userId) {

            $company = Company::create([
                'company_name' => $data['company_name'],
                'company_code' => $this->generateCompanyCode($data['company_name']),

                'company_email' => $data['company_email'] ?? null,
                'company_phone' => $data['company_phone'] ?? null,
                'company_address' => $data['company_address'] ?? null,

                'business_type' => $data['business_type'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,

                'authorized_representative_name' => $data['authorized_representative_name'] ?? null,
                'authorized_representative_position' => $data['authorized_representative_position'] ?? null,
                'authorized_representative_contact' => $data['authorized_representative_contact'] ?? null,

                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $this->storeDocIfPresent($company, 'SEC_CERT', $request->file('sec_cert'), $userId);
            $this->storeDocIfPresent($company, 'DTI_CERT', $request->file('dti_cert'), $userId);
            $this->storeDocIfPresent($company, 'MAYORS_PERMIT', $request->file('mayors_permit'), $userId);
            $this->storeDocIfPresent($company, 'BIR_2303', $request->file('bir_2303'), $userId);
            $this->storeDocIfPresent($company, 'AUTHORIZATION_LETTER', $request->file('authorization_letter'), $userId);

            $this->syncDocsCompletedStatus($company, $userId);

            return $company;
        });
    }

    public function updateCompany(Company $company, array $data, int $userId): Company
    {
        if (
            array_key_exists('company_name', $data)
            && $data['company_name'] !== $company->company_name
        ) {
            $data['company_code'] = $this->generateCompanyCode($data['company_name']);
        } else {
            unset($data['company_code']);
        }

        $data['updated_by'] = $userId;
        $company->update($data);

        return $company;
    }

    public function updateCompanyWithDocuments(Company $company, array $data, Request $request, int $userId): Company
    {
        return DB::transaction(function () use ($company, $data, $request, $userId) {

            // ✅ IMPORTANT: remove file keys so Company::update() doesn't try to save them
            unset(
                $data['sec_cert'],
                $data['dti_cert'],
                $data['mayors_permit'],
                $data['bir_2303'],
                $data['authorization_letter']
            );

            if (
                array_key_exists('company_name', $data)
                && $data['company_name'] !== $company->company_name
            ) {
                $data['company_code'] = $this->generateCompanyCode($data['company_name']);
            } else {
                unset($data['company_code']);
            }

            $data['updated_by'] = $userId;
            $company->update($data);

            $this->storeDocReplacingOldIfPresent($company, 'SEC_CERT', $request->file('sec_cert'), $userId);
            $this->storeDocReplacingOldIfPresent($company, 'DTI_CERT', $request->file('dti_cert'), $userId);
            $this->storeDocReplacingOldIfPresent($company, 'MAYORS_PERMIT', $request->file('mayors_permit'), $userId);
            $this->storeDocReplacingOldIfPresent($company, 'BIR_2303', $request->file('bir_2303'), $userId);
            $this->storeDocReplacingOldIfPresent($company, 'AUTHORIZATION_LETTER', $request->file('authorization_letter'), $userId);

            $this->syncDocsCompletedStatus($company, $userId);

            return $company->fresh();
        });
    }

    public function deleteCompany(Company $company, int $userId): bool
    {
        $company->deleted_by = $userId;
        $company->save();

        return $company->delete();
    }

    public function restoreCompany(Company $company): bool
    {
        return $company->restore();
    }

    public function forceDeleteCompany(Company $company): bool
    {
        return $company->forceDelete();
    }

    protected function storeDocIfPresent(
        Company $company,
        string $docType,
        ?UploadedFile $file,
        int $userId
    ): void {
        if (!$file) return;

        $path = $file->store("companies/{$company->id}/documents", 'public');

        CompanyDocument::updateOrCreate(
            [
                'company_id' => $company->id,
                'doc_type' => $docType,
            ],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),

                'status' => 'pending',
                'remarks' => null,

                'uploaded_by' => $userId,
                'verified_by' => null,
                'verified_at' => null,
            ]
        );
    }

    protected function storeDocReplacingOldIfPresent(
        Company $company,
        string $docType,
        ?UploadedFile $file,
        int $userId
    ): void {
        if (!$file) return;

        $existing = CompanyDocument::where('company_id', $company->id)
            ->where('doc_type', $docType)
            ->first();

        if ($existing?->file_path && Storage::disk('public')->exists($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $path = $file->store("companies/{$company->id}/documents", 'public');

        CompanyDocument::updateOrCreate(
            [
                'company_id' => $company->id,
                'doc_type' => $docType,
            ],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),

                'status' => 'pending',
                'remarks' => null,

                'uploaded_by' => $userId,
                'verified_by' => null,
                'verified_at' => null,
            ]
        );
    }

    protected function syncDocsCompletedStatus(Company $company, int $userId): void
    {
        if (!filled($company->business_type)) {
            return;
        }

        $required = $company->business_type === 'corporate'
            ? ['SEC_CERT', 'MAYORS_PERMIT', 'BIR_2303']
            : ['DTI_CERT', 'MAYORS_PERMIT', 'BIR_2303'];

        $existing = $company->documents()->pluck('doc_type')->unique()->all();

        $complete = empty(array_diff($required, $existing));

        if ($complete) {
            $company->update([
                'status' => 'docs_completed',
                'updated_by' => $userId,
            ]);
        }
    }

    protected function generateCompanyCode(string $companyName): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', $companyName));
        $base = substr($clean, 0, 3);

        if (strlen($base) < 3) {
            $base = str_pad($base, 3, 'X');
        }

        $code = $base;
        $i = 1;

        while (Company::where('company_code', $code)->exists()) {
            $code = $base . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $i++;
        }

        return $code;
    }
}
