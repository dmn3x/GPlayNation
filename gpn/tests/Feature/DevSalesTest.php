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

class DevSalesTest extends TestCase
{
    use RefreshDatabase;

    private function settledSale(Game $game, string $total, string $paidAt): Transaksi
    {
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id, 'total' => $total]);
        Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'paid_at' => $paidAt]);
        KomisiTransaksi::factory()->create([
            'transaksi_id' => $trx->id,
            'amount' => bcmul($total, '0.2', 2),
            'developer_amount' => bcmul($total, '0.8', 2),
        ]);

        return $trx;
    }

    public function test_sales_page_filters_by_month_and_game(): void // SRS 3.1.5.1
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $gameA = Game::factory()->for($dev->developer)->published()->create(['name' => 'Game A']);
        $gameB = Game::factory()->for($dev->developer)->published()->create(['name' => 'Game B']);
        $this->settledSale($gameA, '100000.00', '2026-03-10 12:00:00');
        $this->settledSale($gameB, '50000.00', '2026-04-02 12:00:00');

        $this->actingAs($dev)->get('/dev/sales?month=2026-03')
            ->assertOk()
            ->assertSee('Rp 100.000')
            ->assertDontSee('Rp 50.000');

        $this->actingAs($dev)->get('/dev/sales?month=2026-03&game_id='.$gameB->id)
            ->assertSee('No sales in this period'); // SRS alternate flow
    }

    public function test_sales_kpis_show_gross_commission_net(): void // SRS 3.1.5.2
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->settledSale($game, '100000.00', now()->toDateTimeString());
        Download::factory()->count(3)->create(['game_id' => $game->id]);

        $this->actingAs($dev)->get('/dev/sales?month='.now()->format('Y-m'))
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 20.000')
            ->assertSee('Rp 80.000');
    }

    public function test_other_developers_sales_are_hidden(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $other = User::factory()->verifiedDeveloper()->create();
        $foreign = Game::factory()->for($other->developer)->published()->create();
        $this->settledSale($foreign, '999000.00', now()->toDateTimeString());

        $this->actingAs($dev)->get('/dev/sales')->assertDontSee('Rp 999.000');
    }
}
