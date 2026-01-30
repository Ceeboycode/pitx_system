<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

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


    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
