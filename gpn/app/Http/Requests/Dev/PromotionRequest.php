<?php

namespace App\Http\Requests\Dev;

use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
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
            'game_id' => ['required', 'exists:games,id'],
            'discount_percent' => ['required', 'integer', 'min:1', 'max:90'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at', function ($attribute, $value, $fail) {
                $overlap = \App\Models\Promotion::where('game_id', $this->input('game_id'))
                    ->where('ends_at', '>', $this->input('starts_at'))
                    ->where('starts_at', '<', $value)
                    ->exists();
                if ($overlap) {
                    $fail('This game already has a promotion in that period.');
                }
            }],
        ];
    }
}
