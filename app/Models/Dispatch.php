<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispatch extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_DEPARTED = 'departed';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'gate_id',
        'plate_number',
        'pax_count',
        'bay_number',
        'remarks',
        'dispatcher_user_id',
        'driver_user_id',
        'arrived_at',
        'departed_at',
        'dispatched_at',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'departed_at' => 'datetime',
        'dispatched_at' => 'datetime',
    ];

    protected $appends = [
        'arrived_at_formatted',
        'departed_at_formatted',
    ];

    public function getArrivedAtFormattedAttribute(): ?string
    {
        return $this->arrived_at
            ? $this->arrived_at->timezone('Asia/Manila')->format('M d, Y h:i A')
            : null;
    }

    public function getDepartedAtFormattedAttribute(): ?string
    {
        return $this->departed_at
            ? $this->departed_at->timezone('Asia/Manila')->format('M d, Y h:i A')
            : null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_user_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_user_id');
}

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function changeRequests()
    {
        return $this->hasMany(DispatchChangeRequest::class);
    }

    public function latestChangeRequest()
    {
        return $this->hasOne(DispatchChangeRequest::class)->latestOfMany();
    }
}
