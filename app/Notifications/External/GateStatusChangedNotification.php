<?php

namespace App\Notifications\External;

use App\Models\Gate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GateStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Gate $gate,
        public string $status,
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
            'type' => 'external_gate_status_changed',
            'title' => 'Gate status updated',
            'message' => "Gate {$this->gate->gate_name} is now {$this->status}.",
            'gate_id' => $this->gate->id,
            'gate_name' => $this->gate->gate_name,
            'status' => $this->status,
            'bays' => $this->gate->bays,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Gate Status Updated')
            ->greeting('Hello,')
            ->line("Gate {$this->gate->gate_name} has been marked {$this->status}.")
            ->line('This may affect routes and vehicles assigned under this gate.')
            ->action('Open Company Portal', route('company.dashboard'))
            ->line('Please review your current operations.');
    }
}
