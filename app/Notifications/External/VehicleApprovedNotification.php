<?php

namespace App\Notifications\External;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Vehicle $vehicle,
    ) {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'external_vehicle_approved',
            'title' => 'Vehicle approved',
            'message' => "Vehicle {$this->vehicle->plate_number} has been approved.",
            'vehicle_id' => $this->vehicle->id,
            'company_id' => $this->vehicle->company_id,
            'plate_number' => $this->vehicle->plate_number,
            'status' => $this->vehicle->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Approved')
            ->greeting('Hello,')
            ->line("Your vehicle {$this->vehicle->plate_number} has been approved.")
            ->action('Open Vehicle Record', route('company.vehicles.show', $this->vehicle->id))
            ->line('Thank you.');
    }
}
