<?php

namespace App\Models;

use App\Services\Company\CompanyStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyProfileChangeRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'requested_by',
        'approved_by',
        'status',
        'requested_values',
        'current_values',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'requested_values' => 'array',
        'current_values' => 'array',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function approve(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->refresh();

            if (! $this->isPending()) {
                return;
            }

            $company = $this->company()->lockForUpdate()->firstOrFail();
            $requestedValues = $this->requested_values ?? [];
            $supportingDocuments = $requestedValues['_supporting_documents'] ?? [];

            unset($requestedValues['_supporting_documents']);

            if (array_key_exists('logo', $requestedValues)) {
                $newLogoPath = $requestedValues['logo'];

                if ($company->logo && $company->logo !== $newLogoPath && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }
            }

            $company->fill($requestedValues);
            if ($this->hasMajorChange($requestedValues) && $company->status === Company::STATUS_VERIFIED) {
                $company->status = Company::STATUS_FOR_VERIFICATION;
            }
            $company->save();

            if (! empty($supportingDocuments)) {
                foreach ($supportingDocuments as $doc) {
                    $docType = $doc['doc_type'] ?? null;
                    $filePath = $doc['file_path'] ?? null;

                    if (! $docType || ! $filePath) {
                        continue;
                    }

                    $existing = CompanyDocument::query()
                        ->where('company_id', $company->id)
                        ->where('doc_type', $docType)
                        ->first();

                    if ($existing && $existing->file_path !== $filePath && Storage::disk('public')->exists($existing->file_path)) {
                        Storage::disk('public')->delete($existing->file_path);
                    }

                    $extFromPath = pathinfo((string) $filePath, PATHINFO_EXTENSION);
                    $extFromOriginal = pathinfo((string) ($doc['original_name'] ?? ''), PATHINFO_EXTENSION);
                    $extension = strtolower((string) ($extFromPath ?: $extFromOriginal ?: 'pdf'));

                    $uniformOriginal = $this->nextUniformOriginalName(
                        companyId: $company->id,
                        base: $this->companySlugUpper($company->company_name) . '_' . $docType,
                        ext: $extension,
                        excludingDocumentId: $existing?->id,
                    );

                    $duplicates = CompanyDocument::query()
                        ->where('company_id', $company->id)
                        ->where('doc_type', $docType)
                        ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                        ->get();

                    foreach ($duplicates as $duplicate) {
                        if ($duplicate->file_path && Storage::disk('public')->exists($duplicate->file_path)) {
                            Storage::disk('public')->delete($duplicate->file_path);
                        }

                        $duplicate->delete();
                    }

                    CompanyDocument::query()->updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'doc_type' => $docType,
                        ],
                        [
                            'file_path' => $filePath,
                            'original_name' => $uniformOriginal,
                            'mime_type' => $doc['mime_type'] ?? 'application/octet-stream',
                            'file_size' => (int) ($doc['file_size'] ?? 0),
                            'issued_at' => $doc['issued_at'] ?? null,
                            'expires_at' => $doc['expires_at'] ?? null,
                            'status' => 'verified',
                            'remarks' => null,
                            'uploaded_by' => $this->requested_by,
                            'verified_by' => $user->id,
                            'verified_at' => now(),
                        ]
                    );
                }

                app(CompanyStatusService::class)->syncCompanyStatus($company->fresh());
            }

            if (array_key_exists('business_type', $requestedValues)) {
                $this->cleanupObsoleteBusinessTypeDocuments($company, (string) $company->business_type);
            }

            $this->forceFill([
                'status' => self::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ])->save();
        });
    }

    public function reject(User $user, string $reason): void
    {
        DB::transaction(function () use ($user, $reason): void {
            $this->refresh();

            if (! $this->isPending()) {
                return;
            }

            $requestedValues = $this->requested_values ?? [];
            $logoPath = $requestedValues['logo'] ?? null;
            $companyLogo = $this->company?->logo;
            $supportingDocuments = $requestedValues['_supporting_documents'] ?? [];

            if ($logoPath && $logoPath !== $companyLogo && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            foreach ($supportingDocuments as $doc) {
                $filePath = $doc['file_path'] ?? null;

                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }

            $this->forceFill([
                'status' => self::STATUS_REJECTED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ])->save();
        });
    }

    private function hasMajorChange(array $requestedValues): bool
    {
        return array_key_exists('business_type', $requestedValues)
            || array_key_exists('registration_number', $requestedValues);
    }

    private function cleanupObsoleteBusinessTypeDocuments(Company $company, string $businessType): void
    {
        $obsoleteTypes = $businessType === 'corporate'
            ? ['DTI_CERT']
            : ['SEC_CERT', 'AUTHORIZATION_LETTER'];

        $obsoleteDocs = CompanyDocument::query()
            ->where('company_id', $company->id)
            ->whereIn('doc_type', $obsoleteTypes)
            ->get();

        foreach ($obsoleteDocs as $obsoleteDoc) {
            if ($obsoleteDoc->file_path && Storage::disk('public')->exists($obsoleteDoc->file_path)) {
                Storage::disk('public')->delete($obsoleteDoc->file_path);
            }

            $obsoleteDoc->delete();
        }
    }

    private function companySlugUpper(string $companyName): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]+/', '_', $companyName);
        return strtoupper(trim(preg_replace('/_+/', '_', (string) $clean), '_') ?: 'COMPANY');
    }

    private function nextUniformOriginalName(
        int $companyId,
        string $base,
        string $ext,
        ?int $excludingDocumentId = null,
    ): string {
        $base = strtoupper($base);
        $ext = strtolower($ext);

        $name = "{$base}.{$ext}";

        $exists = CompanyDocument::query()
            ->where('company_id', $companyId)
            ->where('original_name', $name)
            ->when($excludingDocumentId, fn ($query) => $query->where('id', '!=', $excludingDocumentId))
            ->exists();

        if (! $exists) {
            return $name;
        }

        for ($i = 2; ; $i++) {
            $candidate = "{$base}_{$i}.{$ext}";

            $candidateExists = CompanyDocument::query()
                ->where('company_id', $companyId)
                ->where('original_name', $candidate)
                ->when($excludingDocumentId, fn ($query) => $query->where('id', '!=', $excludingDocumentId))
                ->exists();

            if (! $candidateExists) {
                return $candidate;
            }
        }
    }
}
