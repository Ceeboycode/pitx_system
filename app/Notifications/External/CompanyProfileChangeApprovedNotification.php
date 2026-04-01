<?php

namespace App\Notifications\External;

use App\Models\CompanyProfileChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyProfileChangeApprovedNotification extends Notification implements ShouldQueue
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
            'type' => 'external_company_profile_change_approved',
            'title' => 'Company profile update approved',
            'message' => 'Your pending company profile update was approved and applied.',
            'change_request_id' => $this->changeRequest->id,
            'company_id' => $this->changeRequest->company_id,
            'status' => $this->changeRequest->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Company Profile Update Approved')
            ->greeting('Hello,')
            ->line('Your pending company profile update was approved and is now applied.')
            ->action('Open Portal', route('profile'))
            ->line('Thank you.');
    }
}
