<?php

namespace Database\Factories;

use App\Enums\LibrarySource;
use App\Models\Game;
use App\Models\Library;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Library>
 */
class LibraryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
            'transaksi_id' => null,
            'source' => LibrarySource::Purchase,
        ];
    }
}
