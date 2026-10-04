<?php

namespace Database\Factories;

use App\Enums\TransaksiStatus;
use App\Models\Game;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaksi>
 */
class TransaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => fn () => Transaksi::generateOrderId(),
            'user_id' => User::factory(),
            'game_id' => Game::factory()->published(),
            'iap_id' => null,
            'type' => 'game',
            'total' => '75000.00',
            'status' => TransaksiStatus::Pending,
        ];
    }

    /**
     * Indicate that the transaction succeeded.
     */
    public function success(): static
    {
        return $this->state(fn () => ['status' => TransaksiStatus::Success]);
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(fn () => ['status' => TransaksiStatus::Failed]);
    }
}
