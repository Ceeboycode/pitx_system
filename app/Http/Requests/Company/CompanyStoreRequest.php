<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'company_name' => ['required', 'string', 'max:80', 'unique:companies,company_name'],
            'company_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_address' => ['required', 'string', 'max:255'],

            'business_type' => ['required', Rule::in(['corporate', 'sole_proprietorship'])],
            'registration_number' => ['nullable', 'string', 'max:100'],

            'authorized_representative_name' => ['nullable', 'string', 'max:120'],
            'authorized_representative_position' => ['nullable', 'string', 'max:120'],
            'authorized_representative_contact' => ['nullable', 'string', 'max:50'],

            'sec_cert' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'dti_cert' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'mayors_permit' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'bir_2303' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'authorization_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
