<?php

namespace App\Http\Requests\Dev;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

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
                try {
                    $startsAt = Carbon::parse($this->input('starts_at'));
                    $endsAt = Carbon::parse($value);
                } catch (\Throwable) {
                    return; // unparseable input is already flagged by the date rules
                }
                $overlap = Promotion::where('game_id', $this->input('game_id'))
                    ->where('ends_at', '>', $startsAt)
                    ->where('starts_at', '<', $endsAt)
                    ->exists();
                if ($overlap) {
                    $fail('This game already has a promotion in that period.');
                }
            }],
        ];
    }
}
