<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\{Game, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_lists_pending_games_and_approve_publishes(): void // SRS 3-2.3
    {
        $admin = User::factory()->admin()->create();
        $pending = Game::factory()->create(['status' => GameStatus::PendingReview, 'name' => 'Sundel Bolong']);
        Game::factory()->published()->create(['name' => 'Already Live']);

        $this->actingAs($admin)->get('/admin/moderation')
            ->assertSee('Sundel Bolong')->assertDontSee('Already Live');

        $this->actingAs($admin)->post("/admin/moderation/{$pending->id}", ['action' => 'approve']);
        $this->assertSame(GameStatus::Published, $pending->fresh()->status);
        $this->get('/store')->assertSee('Sundel Bolong');
    }

    public function test_reject_requires_feedback_and_shows_it_to_developer(): void // SRS 3-2.3 reject path
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->create(['status' => GameStatus::PendingReview]);

        $this->actingAs($admin)->from("/admin/moderation/{$game->id}")->post("/admin/moderation/{$game->id}", ['action' => 'reject'])
            ->assertSessionHasErrors('feedback');
        $this->actingAs($admin)->post("/admin/moderation/{$game->id}", ['action' => 'reject', 'feedback' => 'Installer corrupted'])
            ->assertRedirect();

        $this->assertSame(GameStatus::Rejected, $game->fresh()->status);
        $this->actingAs($dev)->get('/dev/games')->assertSee('Installer corrupted');
    }

    public function test_resubmitted_game_returns_to_queue(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->create(['status' => GameStatus::Rejected]);

        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => $game->name . ' fixed', 'description' => $game->description, 'genre' => $game->genre, 'price' => '10000',
        ]);
        $this->assertSame(GameStatus::PendingReview, $game->fresh()->status);
        $this->actingAs($admin)->get('/admin/moderation')->assertSee($game->fresh()->name);
    }

    public function test_moderation_detail_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create(['status' => GameStatus::PendingReview, 'name' => 'Detail Render Check']);

        $this->actingAs($admin)->get("/admin/moderation/{$game->id}")
            ->assertOk()
            ->assertSee('Detail Render Check');
    }
}
