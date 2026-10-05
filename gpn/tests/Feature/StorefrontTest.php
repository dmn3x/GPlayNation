<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\LibrarySource;
use App\Models\Download;
use App\Models\Game;
use App\Models\Library;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_only_published_games(): void
    {
        Game::factory()->published()->create(['name' => 'Pocong Runner']);
        Game::factory()->create(['name' => 'Draft Quest', 'status' => GameStatus::PendingReview]);
        $this->get('/')->assertOk()->assertSee('Pocong Runner')->assertDontSee('Draft Quest');
    }

    public function test_store_search_and_genre_filter(): void // SRS 3.1.2.1
    {
        Game::factory()->published()->create(['name' => 'Hantu Laut', 'genre' => 'Horror']);
        Game::factory()->published()->create(['name' => 'Balap Desa', 'genre' => 'Racing']);
        $this->get('/store?q=hantu')->assertSee('Hantu Laut')->assertDontSee('Balap Desa');
        $this->get('/store?genre=Racing')->assertSee('Balap Desa')->assertDontSee('Hantu Laut');
    }

    public function test_store_price_band_boundaries(): void
    {
        $free = Game::factory()->published()->free()->create();
        $cheap = Game::factory()->published()->create(['price' => '49999.00']);
        $mid = Game::factory()->published()->create(['price' => '150000.00']);
        $high = Game::factory()->published()->create(['price' => '150001.00']);

        $this->get('/store?price=free')->assertSee($free->name)->assertDontSee($cheap->name);
        $this->get('/store?price=under50')->assertSee($cheap->name)->assertDontSee($free->name)->assertDontSee($mid->name);
        $this->get('/store?price=50to150')->assertSee($mid->name)->assertDontSee($cheap->name)->assertDontSee($high->name);
        $this->get('/store?price=above150')->assertSee($high->name)->assertDontSee($mid->name);
    }

    public function test_store_empty_state_when_nothing_matches(): void // SRS 3.1.2.1 alternate
    {
        $this->get('/store?q=tidakada')->assertSee('No games found');
    }

    public function test_game_detail_hidden_when_not_published(): void // SRS 3.1.2.2
    {
        $draft = Game::factory()->create();
        $this->get("/store/{$draft->slug}")->assertNotFound();
    }

    public function test_game_detail_shows_promotion_strikethrough(): void
    {
        $game = Game::factory()->published()->create(['price' => '200000.00']);
        Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 25]);
        $this->get("/store/{$game->slug}")
            ->assertSee('Rp 150.000')
            ->assertSee('Rp 200.000');
    }

    public function test_wishlist_toggle_requires_auth_and_toggles(): void
    {
        $game = Game::factory()->published()->create();
        $this->post("/wishlist/{$game->id}")->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->post("/wishlist/{$game->id}");
        $this->assertSame(1, Wishlist::count());
        $this->actingAs($user)->post("/wishlist/{$game->id}");
        $this->assertSame(0, Wishlist::count());
    }

    public function test_free_game_download_claims_library_and_logs(): void // SRS 3.1.2.3
    {
        Storage::fake('public');
        Storage::put('installers/game.zip', 'demo');
        $user = User::factory()->create();
        $game = Game::factory()->published()->free()->create(['file_path' => 'installers/game.zip']);

        $this->actingAs($user)->get("/games/{$game->slug}/download")->assertOk();
        $this->assertDatabaseHas('libraries', ['user_id' => $user->id, 'game_id' => $game->id, 'source' => LibrarySource::Free->value]);
        $this->assertSame(1, Download::count());
    }

    public function test_paid_game_download_redirects_to_purchase_when_unowned(): void // SRS 3.1.2.3 alternate
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '75000.00']);
        $this->actingAs($user)->get("/games/{$game->slug}/download")
            ->assertRedirect("/store/{$game->slug}");
    }

    public function test_owned_game_download_streams_file(): void
    {
        Storage::fake('public');
        Storage::put('installers/game.zip', 'demo');
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['file_path' => 'installers/game.zip']);
        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)->get("/games/{$game->slug}/download")
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_avatar_menu_with_logout_for_authenticated_users(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Log out');

        $user = User::factory()->create();
        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Log out', escape: false)
            ->assertSee(route('profile.show'), escape: false)
            ->assertSee(route('transactions.index'), escape: false)
            ->assertDontSee('Developer portal')
            ->assertDontSee('Admin panel');
    }

    public function test_avatar_menu_shows_role_links(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $this->actingAs($dev)->get('/')->assertSee('Developer portal')->assertDontSee('Admin panel');
        auth()->logout();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/')->assertSee('Admin panel')->assertDontSee('Developer portal');
    }

    public function test_rejected_game_download_is_not_found_for_non_owner(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['status' => GameStatus::Rejected]);

        $this->actingAs($user)->get("/games/{$game->slug}/download")->assertNotFound();
    }

    public function test_free_unpublished_game_download_is_not_found_and_claims_nothing(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->free()->create(['status' => GameStatus::PendingReview]);

        $this->actingAs($user)->get("/games/{$game->slug}/download")->assertNotFound();
        $this->assertDatabaseMissing('libraries', ['user_id' => $user->id, 'game_id' => $game->id]);
    }

    public function test_owner_of_archived_game_can_still_download(): void
    {
        Storage::fake('public');
        Storage::put('installers/game.zip', 'demo');
        $user = User::factory()->create();
        $game = Game::factory()->create(['status' => GameStatus::Archived, 'file_path' => 'installers/game.zip']);
        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)->get("/games/{$game->slug}/download")->assertOk();
    }

    public function test_wishlist_toggle_is_friendly_when_row_already_exists(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();
        Wishlist::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);

        $this->actingAs($user)->from('/store')->post("/wishlist/{$game->id}")
            ->assertRedirect('/store')
            ->assertSessionHas('success');
    }
}
