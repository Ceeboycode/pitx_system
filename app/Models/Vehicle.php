<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plate_number',
        'body_number',
        'capacity',
        'company_id',
        'route_id',
        'vehicle_type_id',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
    'created_at_human',
    'updated_at_human',
    'deleted_at_human',
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
            function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('body_number', 'like', "%{$search}%")
                        ->orWhere('capacity', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($c) =>
                            $c->where('company_name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('route', fn ($r) =>
                            $r->where('route_name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('vehicleType', fn ($t) =>
                            $t->where('type_name', 'like', "%{$search}%")
                        );
                });
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
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
    
    /*
    |--------------------------------------------------------------------------
    | Accessors
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

}
