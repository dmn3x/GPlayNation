<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_lists_own_transactions_newest_first(): void // SRS 3.1.3.4
    {
        $user = User::factory()->create();
        $old = Transaksi::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(2)]);
        $new = Transaksi::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
        Transaksi::factory()->create(); // someone else's

        $this->actingAs($user)->get('/transactions')
            ->assertOk()
            ->assertSeeInOrder([$new->order_id, $old->order_id])
            ->assertViewHas('transaksis', fn ($t) => $t->count() === 2);
    }

    public function test_empty_history_state(): void // SRS 3.1.3.4 alternate
    {
        $this->actingAs(User::factory()->create())->get('/transactions')->assertSee('No transactions yet');
    }

    public function test_detail_shows_payment_and_status(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->success()->create(['user_id' => $user->id]);
        Pembayaran::factory()->create(['transaksi_id' => $trx->id, 'reference_no' => 'REF-ABC123']);

        $this->actingAs($user)->get("/transactions/{$trx->order_id}")
            ->assertOk()
            ->assertSee('REF-ABC123')
            ->assertSee('Success');
    }

    public function test_detail_forbidden_for_other_users(): void
    {
        $trx = Transaksi::factory()->create();
        $this->actingAs(User::factory()->create())->get("/transactions/{$trx->order_id}")->assertForbidden();
    }
}
