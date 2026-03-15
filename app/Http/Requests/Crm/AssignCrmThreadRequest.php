<?php

namespace App\Http\Requests\Crm;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignCrmThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('super-admin');
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $assigneeId = $this->integer('assigned_to_user_id');

            if (! $assigneeId) {
                return;
            }

            $assignee = User::query()->find($assigneeId);

            if (! $assignee || ! $assignee->hasRole('admin')) {
                $validator->errors()->add(
                    'assigned_to_user_id',
                    'The selected assignee must have the admin role.'
                );
            }
        });
    }
}
