<?php

namespace App\Notifications\External;

use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyNeedsRevisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Company $company,
        public CompanyDocument $document,
        public string $remarks,
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
            'type' => 'external_company_needs_revision',
            'title' => 'Document needs revision',
            'message' => "{$this->document->doc_type} was marked invalid. Please review the remarks and resubmit.",
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'document_id' => $this->document->id,
            'doc_type' => $this->document->doc_type,
            'remarks' => $this->remarks,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Needs Revision')
            ->greeting('Hello,')
            ->line("A document for {$this->company->company_name} needs correction.")
            ->line("Document type: {$this->document->doc_type}")
            ->line("Remarks: {$this->remarks}")
            ->action('Review and Resubmit', route('registration.status'))
            ->line('Please upload the corrected document as soon as possible.');
    }
}
