<?php

namespace App\Notifications\External;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleSuspendedNotification extends Notification implements ShouldQueue
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
            'type' => 'external_vehicle_suspended',
            'title' => 'Vehicle suspended',
            'message' => "Vehicle {$this->vehicle->plate_number} has been suspended.",
            'vehicle_id' => $this->vehicle->id,
            'company_id' => $this->vehicle->company_id,
            'plate_number' => $this->vehicle->plate_number,
            'body_number' => $this->vehicle->body_number,
            'status' => $this->vehicle->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Suspended')
            ->greeting('Hello,')
            ->line("Your vehicle {$this->vehicle->plate_number} has been suspended.")
            ->line('Please review the vehicle record or contact support for more information.')
            ->action('Open Vehicle Record', route('company.vehicles.show', $this->vehicle->id))
            ->line('Thank you.');
    }
}
