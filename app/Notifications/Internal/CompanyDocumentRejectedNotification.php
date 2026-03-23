<?php

namespace App\Notifications\Internal;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompanyDocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Company $company,
        public CompanyDocument $document,
        public User $rejectedBy,
        public string $remarks,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'internal_company_document_rejected',
            'title' => 'Company document rejected',
            'message' => "{$this->document->doc_type} for {$this->company->company_name} was marked invalid.",
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'document_id' => $this->document->id,
            'doc_type' => $this->document->doc_type,
            'remarks' => $this->remarks,
            'rejected_by_user_id' => $this->rejectedBy->id,
            'rejected_by_name' => $this->rejectedBy->name,
        ];
    }
}
