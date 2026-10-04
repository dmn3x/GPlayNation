<?php

namespace Tests\Feature;

use App\Models\Developer;
use App\Models\Game;
use App\Models\Komisi;
use App\Models\Library;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS 3.1.3 — Modul Transaksi dan Pembayaran; activity diagrams 3-2.2 and 3-2.4.
 */
class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private function publishedGame(array $overrides = []): Game
    {
        $developer = Developer::factory()->create([
            'user_id' => User::factory()->developer()->create()->id,
        ]);

        return Game::factory()->create([
            'developer_id' => $developer->id,
            'game_status' => 'published',
            'game_harga' => 100000,
            ...$overrides,
        ]);
    }

    public function test_purchase_creates_a_pending_transaction_with_no_commission(): void
    {
        $user = User::factory()->create();
        $game = $this->publishedGame();
        Komisi::create([
            'admin_id' => User::factory()->admin()->create()->id,
            'komisi_persentase' => 20,
            'komisi_berlaku_mulai' => now()->startOfYear(),
        ]);

        $this->actingAs($user)
            ->post("/checkout/game/{$game->id}", ['setuju' => 1])
            ->assertOk();

        $transaksi = Transaksi::firstWhere('game_id', $game->id);

        $this->assertSame('pending', $transaksi->transaksi_status);

        // F-08: commission accrues at settlement, so an unsettled order is not a payable
        $this->assertDatabaseMissing('komisi_transaksi', ['transaksi_id' => $transaksi->id]);
        $this->assertDatabaseMissing('library', [
            'pengguna_id' => $user->id,
            'game_id' => $game->id,
        ]);
    }

    public function test_unsigned_webhook_notification_is_rejected(): void
    {
        $game = $this->publishedGame();
        $transaksi = Transaksi::create([
            'pengguna_id' => User::factory()->create()->id,
            'game_id' => $game->id,
            'transaksi_tanggal' => now(),
            'transaksi_total_harga' => 100000,
            'transaksi_status' => 'pending',
        ]);

        $this->postJson('/webhook/midtrans', [
            'order_id' => (string) $transaksi->id,
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'forged',
        ])->assertForbidden();

        // 3-2.4: reject, log, and halt
        $this->assertSame('pending', $transaksi->fresh()->transaksi_status);
    }

    public function test_signed_webhook_settles_the_transaction_and_grants_the_game(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $user = User::factory()->create();
        $game = $this->publishedGame();
        $transaksi = Transaksi::create([
            'pengguna_id' => $user->id,
            'game_id' => $game->id,
            'transaksi_tanggal' => now(),
            'transaksi_total_harga' => 100000,
            'transaksi_status' => 'pending',
        ]);

        Komisi::create([
            'admin_id' => User::factory()->admin()->create()->id,
            'komisi_persentase' => 20,
            'komisi_berlaku_mulai' => now()->startOfYear(),
        ]);

        $this->postJson('/webhook/midtrans', $this->signedPayload($transaksi->id, 'settlement'))
            ->assertOk();

        $this->assertSame('settlement', $transaksi->fresh()->transaksi_status);

        // 3.2.5: successful payment adds the product to the library
        $this->assertDatabaseHas('library', [
            'pengguna_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'transaksi_id' => $transaksi->id,
            'metode' => 'gopay',
        ]);

        // F-08: 20% of 100000
        $this->assertDatabaseHas('komisi_transaksi', [
            'transaksi_id' => $transaksi->id,
            'nominal' => 20000,
        ]);
    }

    public function test_replayed_webhook_does_not_double_credit(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $user = User::factory()->create();
        $game = $this->publishedGame();
        $transaksi = Transaksi::create([
            'pengguna_id' => $user->id,
            'game_id' => $game->id,
            'transaksi_tanggal' => now(),
            'transaksi_total_harga' => 100000,
            'transaksi_status' => 'pending',
        ]);

        Komisi::create([
            'admin_id' => User::factory()->admin()->create()->id,
            'komisi_persentase' => 20,
            'komisi_berlaku_mulai' => now()->startOfYear(),
        ]);

        $payload = $this->signedPayload($transaksi->id, 'settlement');

        $this->postJson('/webhook/midtrans', $payload)->assertOk();
        $this->postJson('/webhook/midtrans', $payload)->assertOk();

        $this->assertSame(1, Library::where('game_id', $game->id)->count());
        $this->assertSame(1, \App\Models\KomisiTransaksi::where('transaksi_id', $transaksi->id)->count());
    }

    public function test_failed_payment_is_recorded_without_granting_the_game(): void
    {
        config(['services.midtrans.server_key' => 'test-server-key']);

        $user = User::factory()->create();
        $game = $this->publishedGame();
        $transaksi = Transaksi::create([
            'pengguna_id' => $user->id,
            'game_id' => $game->id,
            'transaksi_tanggal' => now(),
            'transaksi_total_harga' => 100000,
            'transaksi_status' => 'pending',
        ]);

        $this->postJson('/webhook/midtrans', $this->signedPayload($transaksi->id, 'expire'))
            ->assertOk();

        $this->assertSame('failed', $transaksi->fresh()->transaksi_status);
        $this->assertDatabaseMissing('library', ['game_id' => $game->id]);
    }

    public function test_webhook_without_configured_server_key_fails_closed(): void
    {
        config(['services.midtrans.server_key' => null]);

        $transaksi = Transaksi::create([
            'pengguna_id' => User::factory()->create()->id,
            'game_id' => $this->publishedGame()->id,
            'transaksi_tanggal' => now(),
            'transaksi_total_harga' => 100000,
            'transaksi_status' => 'pending',
        ]);

        $this->postJson('/webhook/midtrans', $this->signedPayload($transaksi->id, 'settlement'))
            ->assertStatus(503);

        $this->assertSame('pending', $transaksi->fresh()->transaksi_status);
    }

    public function test_owned_paid_game_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $game = $this->publishedGame();
        Library::create(['pengguna_id' => $user->id, 'game_id' => $game->id, 'jam_main' => 0]);

        $this->actingAs($user)->get("/store/{$game->id}/download")->assertOk();

        $this->assertSame(1, $game->fresh()->unduhan);
    }

    public function test_paid_game_download_requires_ownership(): void
    {
        $user = User::factory()->create();
        $game = $this->publishedGame(['game_harga' => 100000]);

        $this->actingAs($user)->get("/store/{$game->id}/download")->assertRedirect();
        $this->assertSame(0, $game->fresh()->unduhan);
    }

    public function test_buying_the_same_game_twice_is_blocked(): void
    {
        $user = User::factory()->create();
        $game = $this->publishedGame();

        Transaksi::create([
            'pengguna_id' => $user->id,
            'game_id' => $game->id,
            'transaksi_tanggal' => now(),
            'transaksi_total_harga' => $game->game_harga,
            'transaksi_status' => 'settlement',
        ]);

        $this->actingAs($user)
            ->get("/checkout/game/{$game->id}")
            ->assertStatus(409);
    }

    private function signedPayload(int $orderId, string $transactionStatus): array
    {
        $orderId = (string) $orderId;
        $statusCode = '200';
        $gross = '100000.00';

        return [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus,
            'status_code' => $statusCode,
            'gross_amount' => $gross,
            'signature_key' => hash('sha512', $orderId.$statusCode.$gross.config('services.midtrans.server_key')),
            'payment_type' => 'gopay',
        ];
    }
}