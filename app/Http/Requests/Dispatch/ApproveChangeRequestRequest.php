<?php

namespace App\Http\Requests\Dispatch;

use Illuminate\Foundation\Http\FormRequest;

class ApproveChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $changeRequest = $this->route('changeRequest');

        // Only internal users (no company_id) can approve
        return $changeRequest && auth()->user()->company_id === null;
    }

    public function rules(): array
    {
        return [
            // Approval doesn't require additional validation fields
            // All validation is implicit (the change request must be valid and pending)
        ];
    }
}
