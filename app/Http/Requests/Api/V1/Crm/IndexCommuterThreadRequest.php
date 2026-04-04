<?php

namespace App\Http\Requests\Api\V1\Crm;

use Illuminate\Foundation\Http\FormRequest;

class IndexCommuterThreadRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'in:facilities,terminal_operations,commuter_app,other'],
            'status' => ['nullable', 'in:open,ongoing,resolved'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
