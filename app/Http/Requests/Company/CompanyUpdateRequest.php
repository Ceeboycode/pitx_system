<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate check is done in the controller
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'docs_completed',
                    'for_verification',
                    'verified',
                    'needs_revision',
                    'rejected',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'company_name.max'      => 'Company name must not exceed 255 characters.',
            'status.required'       => 'Please select a status.',
            'status.in'             => 'The selected status is not valid.',
        ];
    }
}
