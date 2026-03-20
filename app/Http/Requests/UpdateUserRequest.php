<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'phone_number' => ['nullable', 'string', 'max:20'],

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
            },
        ];
    }
}
