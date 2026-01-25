<?php

namespace App\Http\Requests\RouteStop;

use Illuminate\Foundation\Http\FormRequest;

class RouteStopStoreRequest extends FormRequest
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
            'stop_name' => 'required|string|max:255',
            'route_id' => 'required|exists:routes,id',
            'stop_order' => 'required|integer|min:1',
        ];
    }
}
