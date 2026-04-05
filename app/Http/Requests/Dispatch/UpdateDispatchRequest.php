<?php

namespace App\Http\Requests\Dispatch;

use App\Models\Dispatch;
use App\Models\Gate;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Dispatch|null $dispatch */
        $dispatch = $this->route('dispatch');
        $user = $this->user();

        return $user !== null
            && $user->company_id !== null
            && $user->can('external_dispatches.update')
            && $dispatch !== null
            && $dispatch->status !== Dispatch::STATUS_DEPARTED;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id'),
            ],
            'gate_id' => [
                'required',
                'integer',
                Rule::exists('gates', 'id'),
            ],
            'driver_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'bay_number' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) {
                    $gateId = $this->input('gate_id');

                    if (! $gateId) {
                        return;
                    }

                    $gate = Gate::find($gateId);

                    if (! $gate) {
                        return;
                    }

                    $maxBay = (int) $gate->bays;

                    if ($maxBay < 1) {
                        $fail('The selected gate has no available bays.');
                        return;
                    }

                    if ((int) $value > $maxBay) {
                        $fail("The selected bay number is invalid for {$gate->gate_name}. Maximum bay is {$maxBay}.");
                    }
                },
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'Please select a vehicle.',
            'gate_id.required' => 'Please select a gate.',
            'driver_user_id.exists' => 'The selected driver is invalid.',
            'bay_number.required' => 'Please select a bay number.',
        ];
    }
}
