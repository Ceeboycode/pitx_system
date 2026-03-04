<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CompanyDocumentRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controller/policy handles
    }

    public function rules(): array
    {
        return [
            'remarks' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
