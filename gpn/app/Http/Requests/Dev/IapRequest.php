<?php

namespace App\Http\Requests\Dev;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IapRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(['currency', 'item', 'upgrade', 'cosmetic'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
