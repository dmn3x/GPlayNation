<?php

namespace Tests\Feature;

use App\Enums\TransaksiStatus;
use App\Models\Game;
use App\Models\Iap;
use App\Models\Library;
use App\Models\Promotion;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_creates_pending_transaction_and_shows_summary(): void // SRS 3.1.3.1
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '75000.00']);
        $response = $this->actingAs($user)->post("/checkout/game/{$game->id}");
        $trx = Transaksi::sole();
        $this->assertSame(TransaksiStatus::Pending, $trx->status);
        $this->assertSame('75000.00', $trx->total);
        $response->assertRedirect(route('checkout.summary', $trx->order_id));
        $this->get(route('checkout.summary', $trx->order_id))->assertOk()->assertSee('Rp 75.000');
    }

    public function test_promotion_price_is_charged(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '200000.00']);
        Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 25]);
        $this->actingAs($user)->post("/checkout/game/{$game->id}");
        $this->assertSame('150000.00', Transaksi::sole()->total);
    }

    public function test_double_purchase_is_blocked(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();
        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        $this->actingAs($user)->post("/checkout/game/{$game->id}")->assertRedirect(route('store.show', $game->slug));
        $this->assertSame(0, Transaksi::count());
    }

    public function test_free_game_checkout_redirects_to_download(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->free()->create();
        $this->actingAs($user)->post("/checkout/game/{$game->id}")->assertRedirect(route('games.download', $game->slug));
        $this->assertSame(0, Transaksi::count());
    }

    public function test_iap_requires_base_game_ownership(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['price' => '50000.00']);
        $iap = Iap::factory()->for($game)->create();
        $this->actingAs($user)->post("/checkout/iap/{$iap->id}")->assertRedirect(route('store.show', $game->slug));
        $this->assertSame(0, Transaksi::count());

        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        $this->actingAs($user)->post("/checkout/iap/{$iap->id}");
        $this->assertSame(1, Transaksi::where('type', 'iap')->count());
    }

    public function test_pay_requires_agreement_then_redirects_to_gateway(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/checkout/{$trx->order_id}/pay")->assertSessionHasErrors('agreement');
        $this->actingAs($user)->post("/checkout/{$trx->order_id}/pay", ['agreement' => 'on'])
            ->assertRedirect(route('gateway.show', $trx->order_id));
    }

    public function test_simulator_success_settles_and_result_page_shows_success(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/gateway/{$trx->order_id}/complete", ['method' => 'e_wallet', 'outcome' => 'settlement'])
            ->assertRedirect(route('checkout.result', $trx->order_id));
        $this->assertSame(TransaksiStatus::Success, $trx->fresh()->status);
        $this->get(route('checkout.result', $trx->order_id))->assertSee('Game siap dimainkan');
    }

    public function test_simulator_failure_shows_failed_result(): void
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/gateway/{$trx->order_id}/complete", ['method' => 'virtual_account', 'outcome' => 'deny']);
        $this->assertSame(TransaksiStatus::Failed, $trx->fresh()->status);
        $this->get(route('checkout.result', $trx->order_id))->assertSee('Pembayaran gagal');
    }

    public function test_retry_after_failed_payment_creates_new_transaction(): void // Review Focus 2
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create();
        Transaksi::factory()->failed()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        $this->actingAs($user)->post("/checkout/game/{$game->id}");
        $this->assertSame(1, Transaksi::where('status', TransaksiStatus::Pending)->count());
    }

    public function test_cancel_keeps_transaction_pending(): void // SRS 3.1.3.1 alternate
    {
        $user = User::factory()->create();
        $trx = Transaksi::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user)->post("/checkout/{$trx->order_id}/cancel")
            ->assertRedirect(route('store.show', $trx->game->slug));
        $this->assertSame(TransaksiStatus::Pending, $trx->fresh()->status);
    }

    public function test_users_cannot_touch_each_others_checkout(): void
    {
        $trx = Transaksi::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($other)->get("/checkout/{$trx->order_id}")->assertForbidden();
        $this->actingAs($other)->post("/checkout/{$trx->order_id}/pay", ['agreement' => 'on'])->assertForbidden();
        $this->actingAs($other)->post("/gateway/{$trx->order_id}/complete", ['method' => 'e_wallet', 'outcome' => 'settlement'])->assertForbidden();
    }
}
