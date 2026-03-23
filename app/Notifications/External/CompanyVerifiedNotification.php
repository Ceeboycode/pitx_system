<?php

namespace App\Notifications\External;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyVerifiedNotification extends Notification implements ShouldQueue
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
            'type' => 'external_company_verified',
            'title' => 'Company approved',
            'message' => "Your company {$this->company->company_name} has been approved.",
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'status' => $this->company->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Company Registration Approved')
            ->greeting('Hello,')
            ->line("Your company {$this->company->company_name} has been approved.")
            ->action('Open Portal', route('company.dashboard'))
            ->line('Thank you.');
    }
}
