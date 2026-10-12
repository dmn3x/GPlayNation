<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'account_type' => ['required', 'in:user,developer'],
            'studio_name' => ['required_if:account_type,developer', 'nullable', 'string', 'max:120'],
            'portfolio' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ];
    }
}
