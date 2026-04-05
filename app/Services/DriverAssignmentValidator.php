<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\User;
use Carbon\Carbon;

class DriverAssignmentValidator
{
    /**
     * Check if a driver can be assigned on a specific date.
     * A driver cannot be assigned to multiple dispatches on the same day.
     *
     * @param User $driver The driver user
     * @param Carbon|string $date The date to check (will use DATE part only)
     * @param ?Dispatch $excludeDispatch Dispatch to exclude from check (for edit scenarios)
     * @return bool True if driver can be assigned, false if already assigned on that date
     */
    public function canAssignToday(User $driver, Carbon|string $date, ?Dispatch $excludeDispatch = null): bool
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        // Query dispatches where this driver is assigned on the same date
        $query = Dispatch::where('driver_user_id', $driver->id)
            ->where(function ($dateQuery) use ($date) {
                $dateString = $date->toDateString();

                $dateQuery->whereDate('dispatched_at', $dateString)
                    ->orWhere(function ($fallbackQuery) use ($dateString) {
                        $fallbackQuery->whereNull('dispatched_at')
                            ->whereDate('arrived_at', $dateString);
                    })
                    ->orWhere(function ($legacyQuery) use ($dateString) {
                        $legacyQuery->whereNull('dispatched_at')
                            ->whereNull('arrived_at')
                            ->whereDate('created_at', $dateString);
                    });
            })
            ->where('status', '!=', Dispatch::STATUS_DEPARTED); // Don't count departed dispatches

        // If editing existing dispatch, exclude it from the check
        if ($excludeDispatch) {
            $query->where('id', '!=', $excludeDispatch->id);
        }

        return ! $query->exists();
    }

    /**
     * Get a user-friendly validation message
     */
    public function getValidationMessage(User $driver, Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $formattedDate = $date->format('M d, Y');

        return "Driver {$driver->name} is already assigned on {$formattedDate}. Each driver can only be assigned once per day.";
    }
}
