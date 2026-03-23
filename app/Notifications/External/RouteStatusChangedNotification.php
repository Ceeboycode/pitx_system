<?php

namespace App\Notifications\External;

use App\Models\Route;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RouteStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Route $route,
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
            'type' => 'external_route_status_changed',
            'title' => 'Route status updated',
            'message' => "Route {$this->route->route_name} is now {$this->status}.",
            'route_id' => $this->route->id,
            'route_name' => $this->route->route_name,
            'gate_id' => $this->route->gate_id,
            'status' => $this->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Route Status Updated')
            ->greeting('Hello,')
            ->line("Route {$this->route->route_name} has been marked {$this->status}.")
            ->line('This may affect vehicles assigned to this route.')
            ->action('Open Company Portal', route('company.dispatches.index'))
            ->line('Please review your assigned vehicles and schedules.');
    }
}
