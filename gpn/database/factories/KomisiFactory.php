<?php

namespace Database\Factories;

use App\Models\Komisi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Komisi>
 */
class KomisiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->admin(),
            'percentage' => '20.00',
            'effective_from' => today()->subMonth(),
        ];
    }
}
