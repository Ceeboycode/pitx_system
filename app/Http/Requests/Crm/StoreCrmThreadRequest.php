<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreCrmThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'in:compliance,system'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'details' => ['nullable', 'array'],
        ];
    }
}
