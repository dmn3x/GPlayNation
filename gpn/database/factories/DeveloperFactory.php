<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Developer>
 */
class DeveloperFactory extends Factory
{
    protected $model = Developer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->developer(),
            'nama_developer' => fake()->company(),
            'nama_studio' => fake()->company().' Studio',
            'developer_portofolio' => fake()->sentence(),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}