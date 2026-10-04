<?php

namespace Database\Factories;

use App\Models\Iap;
use App\Models\IapOwnership;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IapOwnership>
 */
class IapOwnershipFactory extends Factory
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
            'iap_id' => Iap::factory(),
            'transaksi_id' => Transaksi::factory(),
        ];
    }
}
