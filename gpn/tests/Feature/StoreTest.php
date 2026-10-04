<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Post;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS 3.1.2 — Modul Manajemen Game; F-03 pencarian dan filter.
 */
class StoreTest extends TestCase
{
    use RefreshDatabase;

    private function dev(): User
    {
        $user = User::factory()->developer()->create();

        $user->developer()->create([
            'nama_developer' => 'Studio',
            'nama_studio' => 'Studio',
        ]);

        return $user->fresh();
    }

    public function test_search_filters_by_keyword(): void
    {
        $dev = $this->dev();
        Game::factory()->published()->for($dev->developer, 'developer')->create(['game_nama' => 'Ananta Quest']);
        Game::factory()->published()->for($dev->developer, 'developer')->create(['game_nama' => 'Farm Sim']);

        $this->get('/store?q=Ananta')->assertOk()->assertSee('Ananta Quest')->assertDontSee('Farm Sim');
    }

    public function test_store_only_lists_published_games(): void
    {
        $dev = $this->dev();
        Game::factory()->published()->for($dev->developer, 'developer')->create(['game_nama' => 'Tayang']);
        Game::factory()->pendingReview()->for($dev->developer, 'developer')->create(['game_nama' => 'Disembunyikan']);

        $this->get('/store')->assertOk()->assertSee('Tayang')->assertDontSee('Disembunyikan');
    }

    public function test_price_filter_narrows_results(): void
    {
        $dev = $this->dev();
        Game::factory()->published()->for($dev->developer, 'developer')->create(['game_nama' => 'Mahal', 'game_harga' => 400000]);
        Game::factory()->published()->for($dev->developer, 'developer')->create(['game_nama' => 'Murah', 'game_harga' => 50000]);

        $this->get('/store?harga=murah')->assertOk()->assertSee('Murah')->assertDontSee('Mahal');
    }

    public function test_unpublished_game_detail_is_not_reachable(): void
    {
        $game = Game::factory()->pendingReview()->create(['game_nama' => 'Rahasia']);

        $this->get("/store/{$game->id}")->assertNotFound();
    }

    public function test_developer_upload_lands_in_moderation_queue(): void
    {
        $dev = $this->dev();

        $this->actingAs($dev)->post('/dev/games', [
            'game_nama' => 'Game Baru',
            'deskripsi' => 'Deskripsi game.',
            'game_genre' => 'RPG',
            'kategori' => 'Action',
            'game_harga' => 75000,
        ])->assertRedirect(route('developer.dashboard'));

        // Activity 3-2.1: valid submission waits for admin review, never publishes
        $this->assertDatabaseHas('games', ['game_nama' => 'Game Baru', 'game_status' => 'pending_review']);
    }

    public function test_developer_cannot_edit_another_developers_game(): void
    {
        $dev = $this->dev();

        $other = User::factory()->developer()->create();
        $other->developer()->create(['nama_developer' => 'Lain', 'nama_studio' => 'Lain']);

        $foreign = Game::factory()->create(['developer_id' => $other->developer->id]);

        $this->actingAs($dev)->post("/dev/games/{$foreign->id}", [
            '_method' => 'PUT',
            'game_nama' => 'Dibajak',
            'deskripsi' => 'x',
            'game_harga' => 1,
            'game_status' => 'draft',
        ])->assertForbidden();

        $this->assertDatabaseMissing('games', ['game_nama' => 'Dibajak']);
    }

    public function test_developer_can_add_an_iap_item(): void
    {
        $dev = $this->dev();
        $game = Game::factory()->published()->for($dev->developer, 'developer')->create();

        $this->actingAs($dev)->post("/dev/games/{$game->id}/iaps", [
            'iap_nama' => 'Founder Pack',
            'deskripsi' => 'Bonus aset',
            'iap_harga' => 45000,
            'iap_tipe' => 'Bundle',
        ])->assertRedirect();

        $this->assertDatabaseHas('iaps', ['game_id' => $game->id, 'iap_nama' => 'Founder Pack']);
    }

    public function test_wishlist_toggles(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();

        $this->actingAs($user)->post("/store/{$game->id}/wishlist")->assertRedirect();
        $this->assertDatabaseHas('wishlists', ['pengguna_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)->post("/store/{$game->id}/wishlist")->assertRedirect();
        $this->assertDatabaseMissing('wishlists', ['pengguna_id' => $user->id, 'game_id' => $game->id]);
    }

    public function test_community_post_can_be_created_and_voted(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();

        $this->actingAs($author)->post('/community', ['isi' => 'Guide retracted #ShadowKing'])->assertRedirect();
        $post = Post::firstWhere('isi', 'Guide retracted #ShadowKing');

        $this->actingAs($voter)->post("/community/{$post->id}/vote")->assertRedirect();
        $this->assertSame(1, $post->fresh()->skor);
    }

    public function test_users_cannot_vote_on_their_own_post(): void
    {
        $author = User::factory()->create();
        $post = Post::create(['pengguna_id' => $author->id, 'isi' => 'Postingan sendiri']);

        $this->actingAs($author)->post("/community/{$post->id}/vote")->assertForbidden();
    }
}