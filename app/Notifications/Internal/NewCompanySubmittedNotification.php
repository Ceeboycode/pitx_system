<?php

namespace App\Notifications\Internal;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCompanySubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Company $company,
        public User $submittedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'internal_new_company_submitted',
            'title' => 'New company registration submitted',
            'message' => "{$this->company->company_name} has been submitted for verification.",
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'company_code' => $this->company->company_code,
            'submitted_by_user_id' => $this->submittedBy->id,
            'submitted_by_name' => $this->submittedBy->name,
            'status' => $this->company->status,
        ];
    }
}
