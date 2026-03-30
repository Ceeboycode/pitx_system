<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchChangeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dispatch_id',
        'requested_by',
        'requested_field',
        'old_value',
        'requested_value',
        'reason',
        'status',
        'approved_by',
        'rejection_reason',
        'approved_at',
    ];

    protected $casts = [
        'old_value' => 'json',
        'requested_value' => 'json',
        'approved_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // Field constants
    public const FIELD_DEPARTED_AT = 'departed_at';
    public const FIELD_DRIVER_USER_ID = 'driver_user_id';
    public const FIELD_PAX_COUNT = 'pax_count';
    public const FIELD_VEHICLE_ID = 'vehicle_id';

    // Relationships

    /**
     * Get the dispatch this change request is for
     */
    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    /**
     * Get the user who requested the change (company user)
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved/rejected the request (internal user)
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // Accessors/Attributes

    /**
     * Get human-readable field label
     */
    public function getFieldLabelAttribute(): string
    {
        return match ($this->requested_field) {
            self::FIELD_DEPARTED_AT => 'Departed Time',
            self::FIELD_DRIVER_USER_ID => 'Driver Assignment',
            self::FIELD_PAX_COUNT => 'Passenger Count',
            self::FIELD_VEHICLE_ID => 'Vehicle',
            default => $this->requested_field,
        };
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Approve the change request and apply it to the dispatch
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Apply the change to the dispatch
        $this->applyChange();
    }

    /**
     * Reject the change request
     */
    public function reject(User $rejector, string $rejectionReason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $rejector->id,
            'rejection_reason' => $rejectionReason,
            'approved_at' => now(),
        ]);
    }

    /**
     * Apply the requested change to the dispatch
     */
    protected function applyChange(): void
    {
        $dispatch = $this->dispatch;
        
        // Convert JSON value back to appropriate type and apply
        $value = $this->requested_value;
        
        // Handle special cases for certain fields
        if ($this->requested_field === self::FIELD_DEPARTED_AT) {
            $value = $value ? \Carbon\Carbon::parse($value) : null;
        }

        $dispatch->update([
            $this->requested_field => $value,
        ]);
    }
}
