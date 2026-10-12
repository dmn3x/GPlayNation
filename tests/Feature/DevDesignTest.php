<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevDesignTest extends TestCase
{
    use RefreshDatabase;

    private function dev(): User
    {
        return User::factory()->verifiedDeveloper()->create();
    }

    public function test_dev_dashboard_matches_figma(): void
    {
        $user = $this->dev();
        Game::factory()->for($user->developer)->published()->create();
        $this->actingAs($user)->get('/dev')
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Submit New Game', false)
            ->assertSee('Performance Overview', false)
            ->assertSee('My Published Games', false)
            ->assertSee('DEVELOPER TOOLS', false)
            ->assertDontSee('h-[48px]', false);
    }

    public function test_dev_games_matches_figma(): void
    {
        $user = $this->dev();
        Game::factory()->for($user->developer)->published()->create(['name' => 'Ananta Test']);
        $this->actingAs($user)->get('/dev/games')
            ->assertOk()
            ->assertSee('Manage Games', false)
            ->assertSee('Add New Game', false)
            ->assertSee('Ananta Test', false)
            ->assertSee('Edit Details', false)
            ->assertSee('Archive', false);
    }
}
