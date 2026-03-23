<?php

namespace App\Notifications\External;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleReactivatedNotification extends Notification implements ShouldQueue
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
            'type' => 'external_vehicle_reactivated',
            'title' => 'Vehicle reactivated',
            'message' => "Vehicle {$this->vehicle->plate_number} has been reactivated.",
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
            ->subject('Vehicle Reactivated')
            ->greeting('Hello,')
            ->line("Your vehicle {$this->vehicle->plate_number} has been reactivated.")
            ->line('The vehicle is now active again in the system.')
            ->action('Open Vehicle Record', route('company.vehicles.show', $this->vehicle->id))
            ->line('Thank you.');
    }
}
