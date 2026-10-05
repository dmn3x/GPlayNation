<?php

namespace App\Http\Requests\Dev;

use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
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
            'name' => ['required', 'max:50', 'alpha_dash', 'unique:tags,name'],
        ];
    }
}
