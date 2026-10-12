<?php

namespace App\Http\Requests\Dev;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameUpdateRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:5000'],
            'genre' => ['required', Rule::in(Game::GENRES)],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'screenshots' => ['nullable', 'array', 'max:5'],
            'screenshots.*' => ['image', 'max:2048'],
            'trailer_url' => ['nullable', 'url', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:zip,7z,rar,gz', 'max:51200'],
        ];
    }
}
