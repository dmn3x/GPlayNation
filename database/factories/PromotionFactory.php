<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'discount_percent' => 20,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ];
    }
}
