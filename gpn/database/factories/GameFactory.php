<?php

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Developer;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'developer_id' => Developer::factory(),
            'name' => fake()->unique()->words(3, true),
            'slug' => fn (array $a) => Str::slug($a['name']).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->randomElement(['25000.00', '75000.00', '150000.00']),
            'genre' => fake()->randomElement(Game::GENRES),
            'cover_path' => 'covers/fake.jpg',
            'file_path' => 'installers/fake.zip',
            'status' => GameStatus::Draft,
        ];
    }

    /**
     * Indicate that the game is published.
     */
    public function published(): static
    {
        return $this->state(fn () => ['status' => GameStatus::Published]);
    }

    /**
     * Indicate that the game is free.
     */
    public function free(): static
    {
        return $this->state(fn () => ['price' => '0.00']);
    }
}
