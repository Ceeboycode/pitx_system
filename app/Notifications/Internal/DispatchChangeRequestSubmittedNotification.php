<?php

namespace App\Notifications\Internal;

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
        $companyName = $this->changeRequest->requestedBy->company?->company_name ?? 'Unknown';
        return [
            'type' => 'internal_dispatch_change_request_submitted',
            'title' => 'Dispatch change request submitted',
            'message' => "{$this->changeRequest->requestedBy->name} from {$companyName} has submitted a request to change {$this->changeRequest->field_label} for dispatch {$this->changeRequest->dispatch->plate_number}.",
            'change_request_id' => $this->changeRequest->id,
            'dispatch_id' => $this->changeRequest->dispatch_id,
            'plate_number' => $this->changeRequest->dispatch->plate_number,
            'requested_field' => $this->changeRequest->requested_field,
            'field_label' => $this->changeRequest->field_label,
            'reason' => $this->changeRequest->reason,
            'requested_by_name' => $this->changeRequest->requestedBy->name,
            'requested_by_id' => $this->changeRequest->requested_by,
            'company_name' => $this->changeRequest->requestedBy->company?->company_name,
            'company_code' => $this->changeRequest->requestedBy->company?->company_code,
            'status' => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $companyName = $this->changeRequest->requestedBy->company?->company_name ?? 'Unknown';
        return (new MailMessage)
            ->subject('New Dispatch Change Request')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("A dispatch change request has been submitted by {$this->changeRequest->requestedBy->name} from {$companyName}.")
            ->line("**Dispatch:** {$this->changeRequest->dispatch->plate_number}")
            ->line("**Company:** {$companyName}")
            ->line("**Field to Change:** {$this->changeRequest->field_label}")
            ->line("**Reason:** {$this->changeRequest->reason}")
            ->action('Review Request', route('dispatch-change-requests.show', $this->changeRequest->id))
            ->line('Please review and approve or reject this request.');
    }
}
