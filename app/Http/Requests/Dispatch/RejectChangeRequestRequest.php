<?php

namespace App\Http\Requests\Dispatch;

use Illuminate\Foundation\Http\FormRequest;

class RejectChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $changeRequest = $this->route('changeRequest');

        // Only internal users (no company_id) can reject
        return $changeRequest && auth()->user()->company_id === null;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string|min:5|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'Please provide a reason for rejecting this request.',
            'rejection_reason.min' => 'The rejection reason must be at least 5 characters.',
            'rejection_reason.max' => 'The rejection reason cannot exceed 1000 characters.',
        ];
    }
}
