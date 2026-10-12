<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
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
            'admin_id' => User::factory()->admin(),
            'period' => now()->format('Y-m'),
            'gross' => '1000000.00',
            'commission' => '200000.00',
            'net' => '800000.00',
            'status' => 'processed',
        ];
    }
}
