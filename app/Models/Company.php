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
        // Core identity
        'company_name',
        'company_code',

        // Contact
        'company_email',
        'company_phone',
        'company_address',

        // Business info
        'business_type',          // corporate | sole_proprietorship
        'registration_number',

        // Authorized representative
        'authorized_representative_name',
        'authorized_representative_position',
        'authorized_representative_contact',

        // Workflow
        'status',

        // Auditing
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'created_at_human',
        'updated_at_human',
        'deleted_at_human',
        'is_docs_complete',
        'is_verified',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Relationships (Auditing)
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Relationships (Business)
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Computed attributes
    |--------------------------------------------------------------------------
    */
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

    /**
     * Checks if the required company documents are uploaded.
     *
     * Corporate requires: SEC_CERT + MAYORS_PERMIT + BIR_2303
     * Sole prop requires: DTI_CERT + MAYORS_PERMIT + BIR_2303
     *
     * AUTHORIZATION_LETTER is conditional, so NOT included here.
     */
    public function getIsDocsCompleteAttribute(): bool
    {
        if (!filled($this->business_type)) {
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

    /*
    |--------------------------------------------------------------------------
    | Helpers (optional but useful)
    |--------------------------------------------------------------------------
    */
    public function requiresAuthorizationLetter(?int $transactingUserId = null): bool
    {
        // If you have a field for "authorized representative user id" you can compare properly.
        // For now, just provide a placeholder logic:
        // return $transactingUserId !== $this->authorized_representative_user_id;

        return false;
    }
}
