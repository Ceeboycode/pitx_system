<?php

namespace App\Notifications\External;

use App\Models\CompanyProfileChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyProfileChangeRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CompanyProfileChangeRequest $changeRequest,
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
            'type' => 'external_company_profile_change_rejected',
            'title' => 'Company profile update rejected',
            'message' => 'Your company profile update was rejected. Please review the remarks and resubmit.',
            'change_request_id' => $this->changeRequest->id,
            'company_id' => $this->changeRequest->company_id,
            'rejection_reason' => $this->changeRequest->rejection_reason,
            'status' => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Company Profile Update Rejected')
            ->greeting('Hello,')
            ->line('Your pending company profile update was rejected.')
            ->line('Reason: ' . ($this->changeRequest->rejection_reason ?: 'No remarks provided.'))
            ->action('Review Profile', route('profile'))
            ->line('You may submit a corrected profile update anytime.');
    }
}
