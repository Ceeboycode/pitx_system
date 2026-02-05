<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'created_by',
        'updated_by',
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
        return $this->deleted_at?->diffForHumans();
    }
}
