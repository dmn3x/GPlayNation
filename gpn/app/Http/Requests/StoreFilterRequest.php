<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFilterRequest extends FormRequest
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
            'q' => ['nullable', 'string'],
            'genre' => ['nullable', 'string', Rule::in(Game::GENRES)],
            'tag' => ['nullable', 'string'],
            'price' => ['nullable', 'string', Rule::in(['free', 'under50', '50to150', 'above150'])],
            'sort' => ['nullable', 'string', Rule::in(['newest', 'price_asc', 'price_desc', 'name'])],
        ];
    }
}
