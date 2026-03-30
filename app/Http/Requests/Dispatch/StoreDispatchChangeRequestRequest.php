<?php

namespace App\Http\Requests\Dispatch;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dispatch = $this->route('dispatch');
        $user = auth()->user();

        // Only company users can request changes
        if ($user->company_id === null) {
            return false;
        }

        // User's company must match the dispatch's company
        if ($dispatch->company_id !== $user->company_id) {
            return false;
        }

        // Only departed dispatches can have change requests
        if ($dispatch->status !== 'departed') {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $rules = [
            'requested_field' => 'required|string|in:driver_user_id,pax_count,vehicle_id,gate_id,bay_number',
            'requested_value' => 'required',
            'reason' => 'required|string|min:10|max:1000',
        ];

        // Make requested_value numeric for numeric fields
        $numericFields = ['pax_count', 'gate_id', 'bay_number'];
        if (in_array($this->input('requested_field'), $numericFields, true)) {
            $rules['requested_value'] = 'required|numeric';
        } else {
            $rules['requested_value'] = 'required|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'requested_field.required' => 'Please select a field to change.',
            'requested_field.in' => 'Selected field is not valid.',
            'requested_value.required' => 'Please provide the new value for this field.',
            'requested_value.string' => 'Please provide a valid value for this field.',
            'requested_value.numeric' => 'This field requires a numeric value.',
            'reason.required' => 'Please provide a reason for this change request.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'reason.max' => 'The reason cannot exceed 1000 characters.',
        ];
    }

    protected function failedAuthorization()
    {
        $dispatch = $this->route('dispatch');

        if ($dispatch->status !== 'departed') {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You can only request changes for dispatches that have already departed. Direct editing is available for dispatches that have arrived.'
            );
        }

        throw new \Illuminate\Auth\Access\AuthorizationException('You are not authorized to request changes for this dispatch.');
    }
}
