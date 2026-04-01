<?php

namespace App\Notifications;

use App\Models\DispatchChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DispatchChangeRequestRejectedNotification extends Notification implements ShouldQueue
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
            'type' => 'dispatch_change_request_rejected',
            'title' => 'Dispatch change request rejected',
            'message' => "Your request to change {$this->changeRequest->field_label} for dispatch {$this->changeRequest->dispatch->plate_number} has been rejected.",
            'change_request_id' => $this->changeRequest->id,
            'dispatch_id' => $this->changeRequest->dispatch_id,
            'plate_number' => $this->changeRequest->dispatch->plate_number,
            'requested_field' => $this->changeRequest->requested_field,
            'field_label' => $this->changeRequest->field_label,
            'old_value' => $this->changeRequest->old_value,
            'old_value_display' => $this->changeRequest->old_value_display,
            'requested_value' => $this->changeRequest->requested_value,
            'requested_value_display' => $this->changeRequest->requested_value_display,
            'rejection_reason' => $this->changeRequest->rejection_reason,
            'rejected_by_name' => $this->changeRequest->approvedBy->name ?? 'System',
            'rejected_by_id' => $this->changeRequest->approved_by,
            'status' => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dispatch Change Request Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your request to change {$this->changeRequest->field_label} for dispatch {$this->changeRequest->dispatch->plate_number} has been **rejected**.")
            ->line("- Current Value: {$this->changeRequest->old_value_display}")
            ->line("- Requested Value: {$this->changeRequest->requested_value_display}")
            ->line("**Reason for Rejection:**")
            ->line($this->changeRequest->rejection_reason)
            ->action('View Request', route('dispatches.show', $this->changeRequest->dispatch->id))
            ->line('Please contact support if you have questions.');
    }
}
