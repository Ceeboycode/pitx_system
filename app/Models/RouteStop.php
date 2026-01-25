<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteStop extends Model
{
    //
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'stop_name',
        'route_id',
        'stop_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'created_at_human',
        'updated_at_human',
        'deleted_at_human',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at?->diffForHumans();
    }

    public function getUpdatedAtHumanAttribute()
    {
        return $this->updated_at?->diffForHumans();
    }

    public function getDeletedAtHumanAttribute()
    {
        return $this->deleted_at
            ? $this->deleted_at->diffForHumans()
            : null;
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
