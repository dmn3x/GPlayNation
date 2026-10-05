<?php

namespace Tests\Feature;

use App\Models\{Game, KomisiTransaksi, Pembayaran, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function settledAt(Game $game, string $total, string $paidAt, string $status = 'success'): Transaksi
    {
        $trx = Transaksi::factory()->create(['game_id' => $game->id, 'total' => $total, 'status' => $status]);
        if ($status === 'success') {
            Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'paid_at' => $paidAt]);
            KomisiTransaksi::factory()->create([
                'transaksi_id' => $trx->id,
                'amount' => bcmul($total, '0.2', 2),
                'developer_amount' => bcmul($total, '0.8', 2),
            ]);
        }
        return $trx;
    }

    public function test_month_filter_includes_boundaries(): void // Review Focus 4 — SRS 3.1.5.3
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->published()->create();
        $this->settledAt($game, '10000.00', '2026-03-01 00:00:00');
        $this->settledAt($game, '20000.00', '2026-03-31 23:59:59');
        $this->settledAt($game, '40000.00', '2026-04-01 00:00:00');

        $this->actingAs($admin)->get('/admin/reports?tab=transactions&month=2026-03')
            ->assertSee('Rp 30.000')
            ->assertDontSee('Rp 40.000');
    }

    public function test_transaction_report_filters_by_status(): void // SRS 3.1.5.3
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->published()->create();
        $this->settledAt($game, '10000.00', now()->toDateTimeString());
        Transaksi::factory()->failed()->create(['game_id' => $game->id, 'total' => '99000.00']);

        $this->actingAs($admin)->get('/admin/reports?tab=transactions&status=failed')
            ->assertSee('Rp 99.000')
            ->assertDontSee('Rp 10.000');
    }

    public function test_commission_report_groups_by_developer(): void // SRS 3.1.5.4
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->settledAt($game, '100000.00', now()->toDateTimeString());

        $this->actingAs($admin)->get('/admin/reports?tab=commission&month=' . now()->format('Y-m'))
            ->assertSee($dev->developer->studio_name)
            ->assertSee('Rp 20.000')
            ->assertSee('Rp 80.000');
    }

    public function test_empty_report_state(): void // SRS 3.1.5.3 alternate
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/reports?tab=transactions&month=2020-01')
            ->assertSee('No transactions found');
    }
}
