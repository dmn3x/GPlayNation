<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'developer_id' => Developer::factory(),
            'game_nama' => fake()->unique()->words(2, true),
            'deskripsi' => fake()->paragraph(),
            'game_harga' => fake()->numberBetween(50_000, 350_000),
            'game_genre' => fake()->randomElement(['RPG', 'Platformer', 'Strategy', 'Simulation']),
            'kategori' => fake()->randomElement(['Action', 'Indie', 'Casual']),
            'game_status' => 'draft',
            'rating' => 40,
            'unduhan' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['game_status' => 'published']);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => ['game_status' => 'pending_review']);
    }
}