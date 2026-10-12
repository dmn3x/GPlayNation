<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDesignTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_moderation_matches_figma(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        Game::factory()->for($dev->developer)->create(['name' => 'Ananta Review', 'status' => \App\Enums\GameStatus::PendingReview]);
        $this->actingAs($this->admin())->get('/admin/moderation')
            ->assertOk()
            ->assertSee('Moderasi Game', false)
            ->assertSee('Menunggu Moderasi', false)
            ->assertSee('Pengajuan Game', false)
            ->assertSee('Ananta Review', false)
            ->assertSee('Tinjau Game', false)
            ->assertSee('Menunggu', false)
            ->assertSee('h-[89px]', false)
            ->assertDontSee('h-[48px]', false);
    }

    public function test_admin_tables(): void
    {
        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSee('ADMIN TOOLS', false)
            ->assertSee('Pending developers', false);
        $this->actingAs($this->admin())->get('/admin/moderation')
            ->assertOk();
        \App\Models\Transaksi::factory()->success()->create();
        $this->actingAs($this->admin())->get('/admin/reports')
            ->assertOk()
            ->assertSee('h-[59px]', false);
    }
}
