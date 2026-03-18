<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'company_code',
        'logo',
        'company_email',
        'company_email_verified_at',
        'company_phone',
        'company_address',
        'business_type',
        'registration_number',
        'authorized_representative_name',
        'authorized_representative_position',
        'authorized_representative_contact',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'company_email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'created_at_human',
        'updated_at_human',
        'deleted_at_human',
        'is_docs_complete',
        'is_verified',
        'is_company_email_verified',
    ];

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when(
            filled($search),
            fn (Builder $q) => $q->where(function (Builder $qq) use ($search) {
                $qq->where('company_name', 'like', '%' . $search . '%')
                    ->orWhere('company_code', 'like', '%' . $search . '%')
                    ->orWhere('company_email', 'like', '%' . $search . '%')
                    ->orWhere('company_phone', 'like', '%' . $search . '%');
            })
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getCreatedAtHumanAttribute(): ?string
    {
        return $this->created_at?->diffForHumans();
    }

    public function getUpdatedAtHumanAttribute(): ?string
    {
        return $this->updated_at?->diffForHumans();
    }

    public function getDeletedAtHumanAttribute(): ?string
    {
        return $this->deleted_at?->diffForHumans();
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->status === 'verified';
    }

    public function getIsCompanyEmailVerifiedAttribute(): bool
    {
        return ! is_null($this->company_email_verified_at);
    }

    public function hasVerifiedCompanyEmail(): bool
    {
        return ! is_null($this->company_email_verified_at);
    }

    public function markCompanyEmailAsVerified(): bool
    {
        return $this->forceFill([
            'company_email_verified_at' => now(),
        ])->save();
    }

    public function markCompanyEmailAsUnverified(): bool
    {
        return $this->forceFill([
            'company_email_verified_at' => null,
        ])->save();
    }

    public function getIsDocsCompleteAttribute(): bool
    {
        if (! filled($this->business_type)) {
            return false;
        }

        $required = $this->business_type === 'corporate'
            ? ['SEC_CERT', 'MAYORS_PERMIT', 'BIR_2303']
            : ['DTI_CERT', 'MAYORS_PERMIT', 'BIR_2303'];

        $existing = $this->documents()
            ->pluck('doc_type')
            ->unique()
            ->values()
            ->all();

        return empty(array_diff($required, $existing));
    }

    public function requiresAuthorizationLetter(?int $transactingUserId = null): bool
    {
        return false;
    }
}
