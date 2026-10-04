<?php

namespace Database\Factories;

use App\Enums\IapType;
use App\Models\Game;
use App\Models\Iap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Iap>
 */
class IapFactory extends Factory
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
            'name' => fake()->word(),
            'price' => '10000.00',
            'type' => IapType::Item,
            'is_active' => true,
        ];
    }
}
