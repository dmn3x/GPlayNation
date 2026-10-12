<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_world_renders_every_screen_for_every_role(): void
    {
        Storage::fake('public'); // seeder writes covers/installers — keep them off the real disk
        Artisan::call('db:seed');

        // guest screens
        foreach (['/', '/store', '/community', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }

        // first published game detail + auth screens
        $game = Game::published()->firstOrFail();
        $this->get("/store/{$game->slug}")->assertOk();

        $user = User::where('email', 'user@gplaynation.test')->firstOrFail();
        foreach (['/profile', '/transactions'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }

        $dev = User::where('email', 'dev@gplaynation.test')->firstOrFail();
        foreach (['/dev', '/dev/games', '/dev/games/create', '/dev/sales', '/dev/promotion', '/dev/tags', '/dev/settings', '/dev/payouts'] as $url) {
            $this->actingAs($dev)->get($url)->assertOk();
        }

        $admin = User::where('email', 'admin@gplaynation.test')->firstOrFail();
        foreach (['/admin', '/admin/verification', '/admin/moderation', '/admin/commission', '/admin/payouts', '/admin/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->assertGreaterThanOrEqual(12, Game::published()->count());
        $this->assertGreaterThan(30, Transaksi::count());
    }

    public function test_seeded_game_files_exist_on_disk(): void
    {
        Storage::fake('public');
        Artisan::call('db:seed');
        $game = Game::published()->firstOrFail();
        $this->assertTrue(Storage::disk('public')->exists($game->cover_path));
        $this->assertTrue(Storage::disk('public')->exists($game->file_path));
    }
}
