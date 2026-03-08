<?php

namespace App\Http\Requests\Gate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gate_name' => [
                'required','string','max:80',
                Rule::unique('gates', 'gate_name')
                    ->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'bays' => ['required', 'integer', 'min:0'],
        ];
    }
}
