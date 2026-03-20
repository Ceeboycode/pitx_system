<?php

namespace App\Http\Requests\Dispatch;

use App\Models\Dispatch;
use Illuminate\Foundation\Http\FormRequest;

class DepartDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Dispatch|null $dispatch */
        $dispatch = $this->route('dispatch');

        return auth()->check()
            && auth()->user()?->company_id !== null
            && $dispatch !== null
            && $dispatch->status !== Dispatch::STATUS_DEPARTED;
    }

    public function rules(): array
    {
        return [
            'pax_count' => [
                'required',
                'integer',
                'min:15',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pax_count.required' => 'Passenger count is required before departure.',
            'pax_count.integer' => 'Passenger count must be a valid number.',
            'pax_count.min' => 'Passenger count must be at least 15.',
        ];
    }
}
