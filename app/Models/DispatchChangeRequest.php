<?php

namespace App\Models;

use Carbon\Carbon;
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
    public const FIELD_GATE_ID = 'gate_id';
    public const FIELD_BAY_NUMBER = 'bay_number';

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
            self::FIELD_GATE_ID => 'Gate',
            self::FIELD_BAY_NUMBER => 'Bay Number',
            default => $this->requested_field,
        };
    }

    public function getOldValueDisplayAttribute(): string
    {
        return $this->resolveDisplayValue($this->old_value);
    }

    public function getRequestedValueDisplayAttribute(): string
    {
        return $this->resolveDisplayValue($this->requested_value);
    }

    public function resolveDisplayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($this->requested_field) {
            self::FIELD_DRIVER_USER_ID => $this->resolveDriverName($value),
            self::FIELD_VEHICLE_ID => $this->resolveVehicleLabel($value),
            self::FIELD_GATE_ID => $this->resolveGateName($value),
            self::FIELD_PAX_COUNT,
            self::FIELD_BAY_NUMBER => (string) $value,
            self::FIELD_DEPARTED_AT => $this->formatDateTime($value),
            default => $this->formatScalar($value),
        };
    }

    private function resolveDriverName(mixed $value): string
    {
        if (! is_numeric($value)) {
            return $this->formatScalar($value);
        }

        $id = (int) $value;
        static $driverNames = [];

        if (! array_key_exists($id, $driverNames)) {
            $driverNames[$id] = User::query()->whereKey($id)->value('name');
        }

        return $driverNames[$id] ?? "Unknown Driver (#{$id})";
    }

    private function resolveVehicleLabel(mixed $value): string
    {
        if (! is_numeric($value)) {
            return $this->formatScalar($value);
        }

        $id = (int) $value;
        static $vehicleLabels = [];

        if (! array_key_exists($id, $vehicleLabels)) {
            $vehicle = Vehicle::query()
                ->select(['id', 'plate_number', 'body_number', 'vehicle_type'])
                ->find($id);

            $vehicleLabels[$id] = $vehicle
                ? trim(implode(' • ', array_filter([
                    $vehicle->plate_number,
                    $vehicle->body_number ? 'Body #' . $vehicle->body_number : null,
                    $vehicle->vehicle_type,
                ])))
                : null;
        }

        return $vehicleLabels[$id] ?? "Unknown Vehicle (#{$id})";
    }

    private function resolveGateName(mixed $value): string
    {
        if (! is_numeric($value)) {
            return $this->formatScalar($value);
        }

        $id = (int) $value;
        static $gateNames = [];

        if (! array_key_exists($id, $gateNames)) {
            $gateNames[$id] = Gate::query()->whereKey($id)->value('gate_name');
        }

        return $gateNames[$id] ?? "Unknown Gate (#{$id})";
    }

    private function formatDateTime(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return $this->formatScalar($value);
        }

        try {
            return Carbon::parse((string) $value)->format('M d, Y h:i A');
        } catch (\Throwable) {
            return $this->formatScalar($value);
        }
    }

    private function formatScalar(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value);
        }

        return (string) $value;
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
            $value = is_scalar($value) && $value !== ''
                ? Carbon::parse((string) $value)
                : null;
        }

        $dispatch->update([
            $this->requested_field => $value,
        ]);
    }
}
