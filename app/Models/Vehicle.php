<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    // Required document types must align with external submission doc set
    public const REQUIRED_DOCUMENT_TYPES = [
        'insurance_certificate',
        'cpc',
        'official_receipt',
        'certificate_of_registration',
        'puv_identification_markings',
    ];

    protected $fillable = [
        'company_id',
        'route_id',
        'vehicle_type',
        'plate_number',
        'body_number',
        'capacity',
        'color',
        'engine_number',
        'chassis_number',
        'make_model',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['docs_status'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
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

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('plate_number', 'like', "%{$term}%")
                ->orWhere('body_number', 'like', "%{$term}%")
                ->orWhere('make_model', 'like', "%{$term}%")
                ->orWhere('vehicle_type', 'like', "%{$term}%")
                ->orWhere('status', 'like', "%{$term}%");
        });
    }

    public function getDocsStatusAttribute(): string
    {
        $documents = $this->documents()
            ->whereIn('document_type', self::REQUIRED_DOCUMENT_TYPES)
            ->get();

        if ($documents->isEmpty()) {
            return 'none';
        }

        $verified = $documents->where('status', 'verified')->count();

        if ($verified === count(self::REQUIRED_DOCUMENT_TYPES)) {
            return 'complete';
        }

        return 'partial';
    }
}
