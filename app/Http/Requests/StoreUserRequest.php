<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:20'],

            'type' => ['required', Rule::in(['internal', 'external'])],

            'company_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => $this->input('type') === 'external'),
                'exists:companies,id',
            ],

            'role' => [
                'required',
                'string',
                // role name must exist AND match the selected type
                Rule::exists('roles', 'name')->where(fn ($q) => $q->where('type', $this->input('type'))),
            ],
        ];
    }
}
