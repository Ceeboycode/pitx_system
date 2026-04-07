<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', "regex:/^[A-Za-z][A-Za-z\s.'-]*$/"],

            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'phone_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-()\s]{7,20}$/', Rule::unique('users', 'phone_number')],

            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name'),
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $roleName = $this->input('role');

                if (! $roleName) {
                    return;
                }

                $role = Role::query()
                    ->select('id', 'name', 'type')
                    ->where('name', $roleName)
                    ->first();

                if (! $role) {
                    return;
                }

                if ($role->type === 'external' && ! $this->filled('company_id')) {
                    $validator->errors()->add(
                        'company_id',
                        'The company field is required for external roles.'
                    );
                }

                if ($role->type === 'internal' && $this->filled('company_id')) {
                    $validator->errors()->add(
                        'company_id',
                        'The company field is not allowed for internal roles.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name may only contain letters, spaces, apostrophes, periods, and hyphens.',
        ];
    }
}
