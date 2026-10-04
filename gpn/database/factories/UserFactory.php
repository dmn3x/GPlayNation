<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => Role::User,
            'status' => UserStatus::Active,
        ];
    }

    /**
     * Indicate that the user is a developer awaiting verification.
     */
    public function developer(): static
    {
        return $this->state(fn () => ['role' => Role::Developer, 'status' => UserStatus::Pending])
            ->afterCreating(fn (User $u) => Developer::factory()->create(['user_id' => $u->id]));
    }

    /**
     * Indicate that the user is a verified developer.
     */
    public function verifiedDeveloper(): static
    {
        return $this->developer()->state(fn () => ['status' => UserStatus::Active]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin, 'status' => UserStatus::Active]);
    }
}
