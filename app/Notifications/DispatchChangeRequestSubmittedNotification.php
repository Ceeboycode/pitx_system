<?php

namespace App\Notifications;

use App\Models\DispatchChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DispatchChangeRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DispatchChangeRequest $changeRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'dispatch_change_request_submitted',
            'title' => 'New dispatch change request',
            'message' => "{$this->changeRequest->requestedBy->name} submitted a request to change {$this->changeRequest->field_label} for dispatch {$this->changeRequest->dispatch->plate_number}.",
            'change_request_id' => $this->changeRequest->id,
            'dispatch_id' => $this->changeRequest->dispatch_id,
            'plate_number' => $this->changeRequest->dispatch->plate_number,
            'requested_by_name' => $this->changeRequest->requestedBy->name,
            'requested_by_id' => $this->changeRequest->requested_by,
            'requested_field' => $this->changeRequest->requested_field,
            'field_label' => $this->changeRequest->field_label,
            'old_value' => $this->changeRequest->old_value,
            'requested_value' => $this->changeRequest->requested_value,
            'reason' => $this->changeRequest->reason,
            'status' => $this->changeRequest->status,
            'created_at' => $this->changeRequest->created_at,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Dispatch Change Request Submitted')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("{$this->changeRequest->requestedBy->name} from {$this->changeRequest->requestedBy->company->name} submitted a request to change a dispatch.")
            ->line('**Dispatch Details:**')
            ->line("- Plate Number: {$this->changeRequest->dispatch->plate_number}")
            ->line("- Field: {$this->changeRequest->field_label}")
            ->line("- Current Value: {$this->formatValue($this->changeRequest->old_value)}")
            ->line("- Requested Value: {$this->formatValue($this->changeRequest->requested_value)}")
            ->line('')
            ->line("**Reason:** {$this->changeRequest->reason}")
            ->action('Review Request', route('dispatch-change-requests.index'))
            ->line('Please review and approve or reject this change request.');
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
