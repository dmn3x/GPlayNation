<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_matches_figma(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();
        Transaksi::factory()->for($user)->for($game)->create();
        Transaksi::factory()->for($user)->for($game)->success()->create();

        $this->actingAs($user)->get('/transactions')
            ->assertOk()
            ->assertSee('Riwayat Transaksi', false)
            ->assertSee('Semua Transaksi', false)
            ->assertSee('PRODUK', false)
            ->assertSee('STATUS', false)
            ->assertSee('Detail', false)
            ->assertDontSee('h-[48px]', false);
    }

    public function test_checkout_summary_matches_figma(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();
        $trx = Transaksi::factory()->for($user)->for($game)->create();

        $this->actingAs($user)->get('/checkout/'.$trx->order_id)
            ->assertOk()
            ->assertSee('Proses Pembayaran', false)
            ->assertSee('Pilih Metode Pembayaran', false)
            ->assertSee('Ringkasan Pesanan', false)
            ->assertSee('Pembelian', false)
            ->assertSee('Bayar', false);
    }
}
