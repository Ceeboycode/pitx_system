<?php

namespace App\Notifications\Internal;

use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompanyDocumentVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Company $company,
        public CompanyDocument $document,
        public User $verifiedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'internal_company_document_verified',
            'title' => 'Company document verified',
            'message' => "{$this->document->doc_type} for {$this->company->company_name} was verified.",
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'document_id' => $this->document->id,
            'doc_type' => $this->document->doc_type,
            'verified_by_user_id' => $this->verifiedBy->id,
            'verified_by_name' => $this->verifiedBy->name,
        ];
    }
}
