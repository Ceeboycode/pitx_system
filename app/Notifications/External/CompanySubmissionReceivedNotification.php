<?php

namespace App\Notifications\External;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanySubmissionReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Company $company,
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
            'type' => 'external_company_submission_received',
            'title' => 'Registration submitted',
            'message' => "Your company {$this->company->company_name} has been submitted for verification.",
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'status' => $this->company->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Company Registration Submitted')
            ->greeting('Hello,')
            ->line("Your company {$this->company->company_name} has been submitted for verification.")
            ->line('Our team is now reviewing your registration and uploaded documents.')
            ->action('View Registration Status', route('registration.status'))
            ->line('We will notify you again once the review is completed.');
    }
}
