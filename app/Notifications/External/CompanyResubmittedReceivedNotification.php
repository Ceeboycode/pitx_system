<?php

namespace App\Notifications\External;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyResubmittedReceivedNotification extends Notification implements ShouldQueue
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
            'type' => 'external_company_resubmitted_received',
            'title' => 'Corrected documents submitted',
            'message' => "We received the corrected documents for {$this->company->company_name}.",
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'status' => $this->company->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Corrected Documents Submitted')
            ->greeting('Hello,')
            ->line("We received the corrected documents for {$this->company->company_name}.")
            ->line('Your submission is now back under review.')
            ->action('View Registration Status', route('registration.status'))
            ->line('We will notify you again once the review is completed.');
    }
}
