<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Iap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameManagementTest extends TestCase
{
    use RefreshDatabase;

    private function dev(): User
    {
        return User::factory()->verifiedDeveloper()->create();
    }

    private function uploadPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jelangkung Village',
            'description' => 'A horror adventure through a Javanese village.',
            'genre' => 'Horror',
            'price' => '75000',
            'cover' => UploadedFile::fake()->image('cover.jpg', 1280, 720),
            'screenshots' => [UploadedFile::fake()->image('s1.jpg'), UploadedFile::fake()->image('s2.jpg')],
            'trailer_url' => 'https://example.com/trailer',
            'file' => UploadedFile::fake()->create('game.zip', 1024, 'application/zip'),
        ], $overrides);
    }

    public function test_upload_creates_game_pending_review_with_files(): void // SRS 3.1.2.4 + 3-2.1
    {
        Storage::fake('public');
        $dev = $this->dev();
        $this->actingAs($dev)->post('/dev/games', $this->uploadPayload())->assertRedirect(route('dev.games.index'));

        $game = Game::sole();
        $this->assertSame(GameStatus::PendingReview, $game->status);
        $this->assertSame($dev->developer->id, $game->developer_id);
        Storage::disk('public')->assertExists($game->cover_path);
        Storage::disk('public')->assertExists($game->file_path);
        $this->assertSame(2, $game->screenshots()->count());
    }

    public function test_upload_validation_rejects_bad_file(): void // SRS 3.1.2.4 alternate
    {
        Storage::fake('public');
        $this->actingAs($this->dev())->from('/dev/games/create')
            ->post('/dev/games', $this->uploadPayload(['file' => UploadedFile::fake()->create('virus.exe.php', 10)]))
            ->assertSessionHasErrors('file');
        $this->assertSame(0, Game::count());
    }

    public function test_unverified_developer_is_blocked_from_portal(): void
    {
        $pending = User::factory()->developer()->create();
        $this->actingAs($pending)->get('/dev')->assertForbidden();
    }

    public function test_editing_published_game_returns_it_to_pending_review(): void // moderation-bypass fix
    {
        Storage::fake('public');
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => 'New Title', 'description' => $game->description, 'genre' => $game->genre, 'price' => '80000',
        ])->assertRedirect();
        $this->assertSame(GameStatus::PendingReview, $game->fresh()->status);
    }

    public function test_price_only_change_keeps_game_published(): void // SRS 3.1.2.6
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create(['price' => '50000.00']);
        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => $game->name, 'description' => $game->description, 'genre' => $game->genre, 'price' => '65000',
        ]);
        $fresh = $game->fresh();
        $this->assertSame(GameStatus::Published, $fresh->status);
        $this->assertSame('65000.00', $fresh->price);
    }

    public function test_archive_toggle_is_instant(): void // SRS 3.1.2.6
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->post("/dev/games/{$game->id}/archive");
        $this->assertSame(GameStatus::Archived, $game->fresh()->status);
        $this->actingAs($dev)->post("/dev/games/{$game->id}/archive");
        $this->assertSame(GameStatus::Published, $game->fresh()->status);
    }

    public function test_cross_developer_edit_is_forbidden(): void
    {
        $game = Game::factory()->published()->create();
        $this->actingAs($this->dev())->put("/dev/games/{$game->id}", [
            'name' => 'Hijack', 'description' => $game->description, 'genre' => $game->genre, 'price' => '1',
        ])->assertForbidden();
    }

    public function test_iap_create_edit_deactivate(): void // SRS 3.1.2.5 IAP
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->post("/dev/games/{$game->id}/iaps", [
            'name' => '1000 Coins', 'price' => '15000', 'type' => 'currency', 'description' => 'Coin pack',
        ])->assertRedirect();
        $iap = Iap::sole();
        $this->assertSame('15000.00', $iap->price);

        $this->actingAs($dev)->put("/dev/iaps/{$iap->id}", [
            'name' => '1000 Coins', 'price' => '12000', 'type' => 'currency', 'is_active' => '0',
        ]);
        $this->assertFalse($iap->fresh()->is_active);

        $this->actingAs($dev)->get("/dev/games/{$game->id}/edit")
            ->assertOk()->assertSee('1000 Coins')->assertSee("/dev/iaps/{$iap->id}", escape: false);
    }

    public function test_settings_update_studio_and_payout_details(): void
    {
        $dev = $this->dev();
        $this->actingAs($dev)->post('/dev/settings', [
            'studio_name' => 'Studio Baru', 'portfolio' => 'https://portfolio.example',
            'payout_bank' => 'BCA', 'payout_account' => '1234567890',
        ])->assertRedirect();
        $this->assertSame('BCA', $dev->developer->fresh()->payout_bank);
    }

    public function test_archive_is_forbidden_for_unreviewed_game(): void
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->create(['status' => GameStatus::PendingReview]);
        $this->actingAs($dev)->post("/dev/games/{$game->id}/archive")->assertForbidden();
        $this->assertSame(GameStatus::PendingReview, $game->fresh()->status);
    }

    public function test_trailer_only_change_returns_published_game_to_pending_review(): void
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->published()->create(['trailer_url' => 'https://example.com/old']);
        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => $game->name, 'description' => $game->description, 'genre' => $game->genre,
            'price' => $game->price, 'trailer_url' => 'https://example.com/new',
        ])->assertRedirect();
        $this->assertSame(GameStatus::PendingReview, $game->fresh()->status);
    }

    public function test_editing_rejected_game_returns_it_to_pending_review(): void // SRS 3-2.3 resubmit
    {
        $dev = $this->dev();
        $game = Game::factory()->for($dev->developer)->create(['status' => GameStatus::Rejected, 'rejection_feedback' => 'Fix the cover.']);
        $this->actingAs($dev)->put("/dev/games/{$game->id}", [
            'name' => 'Fixed Title', 'description' => $game->description, 'genre' => $game->genre, 'price' => $game->price,
        ])->assertRedirect();
        $this->assertSame(GameStatus::PendingReview, $game->fresh()->status);
    }
}
