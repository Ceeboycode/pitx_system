<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be a valid mobile number.',
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'string', 'regex:/^\+63[0-9]{10}$/', 'unique:users,phone_number'],
            'username' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:users,username'],
        ];
    }
}
