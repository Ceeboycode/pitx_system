<?php

namespace App\Notifications\Internal;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VehicleResubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Vehicle $vehicle,
        public User $submittedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'internal_vehicle_resubmitted',
            'title' => 'Vehicle updated for review',
            'message' => "Vehicle {$this->vehicle->plate_number} was updated and documents were resubmitted.",
            'vehicle_id' => $this->vehicle->id,
            'company_id' => $this->vehicle->company_id,
            'plate_number' => $this->vehicle->plate_number,
            'body_number' => $this->vehicle->body_number,
            'submitted_by_user_id' => $this->submittedBy->id,
            'submitted_by_name' => $this->submittedBy->name,
            'status' => $this->vehicle->status,
        ];
    }
}
