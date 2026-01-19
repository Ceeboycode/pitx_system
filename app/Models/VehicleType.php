<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'type_name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'created_at_human',
        'updated_at_human',
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

}
