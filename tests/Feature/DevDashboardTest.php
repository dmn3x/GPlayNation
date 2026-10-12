<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\Game;
use App\Models\KomisiTransaksi;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_counts_and_series(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id, 'total' => '100000.00']);
        Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'paid_at' => now()]);
        KomisiTransaksi::factory()->create(['transaksi_id' => $trx->id, 'amount' => '20000.00', 'developer_amount' => '80000.00']);
        Download::factory()->create(['game_id' => $game->id]);

        $this->actingAs($dev)->get('/dev')
            ->assertOk()
            ->assertSee('Active Players')   // downloads proxy
            ->assertSee('Wishlist Adds')
            ->assertSee('Published Games')
            ->assertViewHas('series', fn ($s) => count($s) === now()->daysInMonth);
    }

    public function test_payouts_page_shows_unpaid_balance(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id]);
        KomisiTransaksi::factory()->create(['transaksi_id' => $trx->id, 'developer_amount' => '80000.00', 'payout_id' => null]);

        $this->actingAs($dev)->get('/dev/payouts')->assertSee('Rp 80.000');
    }
}
