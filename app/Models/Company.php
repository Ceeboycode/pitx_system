<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = [
        'created_at_human',
        'updated_at_human',
        'deleted_at_human',
    ];

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when(
            filled($search),
            fn (Builder $q) => $q->where('company_name', 'like', '%' . $search . '%')
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
}
