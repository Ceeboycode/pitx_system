<?php

namespace App\Notifications\Internal;

use App\Models\CompanyProfileChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompanyProfileChangeSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CompanyProfileChangeRequest $changeRequest,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'internal_company_profile_change_submitted',
            'title' => 'Company profile update submitted',
            'message' => "{$this->changeRequest->company->company_name} submitted profile updates for review.",
            'change_request_id' => $this->changeRequest->id,
            'company_id' => $this->changeRequest->company_id,
            'company_name' => $this->changeRequest->company->company_name,
            'requested_by' => $this->changeRequest->requester?->name,
            'status' => $this->changeRequest->status,
        ];
    }
}
