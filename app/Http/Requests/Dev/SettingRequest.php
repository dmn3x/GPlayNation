<?php

namespace App\Http\Requests\Dev;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'studio_name' => ['required', 'string', 'max:120'],
            'portfolio' => ['nullable', 'string', 'max:2000'],
            'payout_bank' => ['nullable', 'string', 'max:100'],
            'payout_account' => ['nullable', 'string', 'max:100'],
        ];
    }
}
