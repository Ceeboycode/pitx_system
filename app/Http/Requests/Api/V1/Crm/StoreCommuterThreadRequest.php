<?php

namespace App\Http\Requests\Api\V1\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommuterThreadRequest extends FormRequest
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
            'category' => ['required', 'in:facilities,terminal_operations,commuter_app,other'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'details' => ['nullable', 'array'],
        ];
    }
}
