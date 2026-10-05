<?php

namespace Tests\Feature;

use App\Enums\{TransaksiStatus, LibrarySource};
use App\Models\{Iap, IapOwnership, Komisi, KomisiTransaksi, Library, Pembayaran, Transaksi, User};
use App\Services\Payment\Signature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function signedPayload(Transaksi $trx, string $status = 'settlement', ?string $amount = null): array
    {
        $gross = $amount ?? Signature::gross($trx->total);
        return [
            'order_id' => $trx->order_id,
            'status_code' => '200',
            'transaction_status' => $status,
            'gross_amount' => $gross,
            'payment_type' => 'e_wallet',
            'signature_key' => Signature::make($trx->order_id, '200', $gross, (string) config('services.gpn_payment.server_key')),
        ];
    }

    public function test_valid_settlement_grants_library_and_snapshots_commission(): void // SRS 3.1.3.3
    {
        Komisi::factory()->create(['percentage' => '20.00', 'effective_from' => today()->subMonth()]);
        $trx = Transaksi::factory()->create(['total' => '100000.00']);

        $this->postJson('/webhooks/payment', $this->signedPayload($trx))->assertOk();

        $trx->refresh();
        $this->assertSame(TransaksiStatus::Success, $trx->status);
        $this->assertDatabaseHas('pembayarans', ['transaksi_id' => $trx->id, 'method' => 'e_wallet']);
        $this->assertDatabaseHas('libraries', ['user_id' => $trx->user_id, 'game_id' => $trx->game_id, 'source' => LibrarySource::Purchase->value]);
        $row = KomisiTransaksi::where('transaksi_id', $trx->id)->sole();
        $this->assertSame('20000.00', $row->amount);
        $this->assertSame('80000.00', $row->developer_amount);
        $this->assertSame('20.00', $row->percentage);
    }

    public function test_forged_signature_is_rejected(): void // SRS 3-2.4 invalid signature
    {
        $trx = Transaksi::factory()->create();
        $payload = $this->signedPayload($trx);
        $payload['signature_key'] = 'forged';
        $this->postJson('/webhooks/payment', $payload)->assertForbidden();
        $this->assertSame(TransaksiStatus::Pending, $trx->fresh()->status);
    }

    public function test_missing_server_key_fails_closed(): void
    {
        config()->set('services.gpn_payment.server_key', null);
        $trx = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($trx))->assertStatus(503);
    }

    public function test_amount_mismatch_is_rejected(): void // Review Focus 1
    {
        $trx = Transaksi::factory()->create(['total' => '100000.00']);
        $this->postJson('/webhooks/payment', $this->signedPayload($trx, 'settlement', '1000.00'))->assertStatus(422);
        $this->assertSame(TransaksiStatus::Pending, $trx->fresh()->status);
    }

    public function test_replay_is_idempotent(): void
    {
        $trx = Transaksi::factory()->create();
        $payload = $this->signedPayload($trx);
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $this->assertSame(1, Library::count());
    }

    public function test_double_settlement_is_idempotent(): void // Review Focus 3
    {
        $trx = Transaksi::factory()->create();
        $payload = $this->signedPayload($trx);
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $payload['payment_type'] = 'credit_card'; // a second, different delivery
        $payload['signature_key'] = Signature::make($trx->order_id, '200', $payload['gross_amount'], (string) config('services.gpn_payment.server_key'));
        $this->postJson('/webhooks/payment', $payload)->assertOk();
        $this->assertSame(1, Library::count());
        $this->assertSame(1, KomisiTransaksi::count());
        $this->assertSame(1, Pembayaran::count());
    }

    public function test_status_mappings(): void
    {
        $pending = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($pending, 'pending'))->assertOk();
        $this->assertSame(TransaksiStatus::Pending, $pending->fresh()->status);

        $denied = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($denied, 'deny'))->assertOk();
        $this->assertSame(TransaksiStatus::Failed, $denied->fresh()->status);

        $expired = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($expired, 'expire'))->assertOk();
        $this->assertSame(TransaksiStatus::Expired, $expired->fresh()->status);
    }

    public function test_capture_settles_like_settlement(): void
    {
        $trx = Transaksi::factory()->create(['total' => '100000.00']);

        $this->postJson('/webhooks/payment', $this->signedPayload($trx, 'capture'))->assertOk();

        $trx->refresh();
        $this->assertSame(TransaksiStatus::Success, $trx->status);
        $this->assertDatabaseHas('pembayarans', ['transaksi_id' => $trx->id, 'method' => 'e_wallet']);
        $this->assertDatabaseHas('libraries', ['user_id' => $trx->user_id, 'game_id' => $trx->game_id, 'source' => LibrarySource::Purchase->value]);
        $this->assertSame(1, KomisiTransaksi::where('transaksi_id', $trx->id)->count());
    }

    public function test_cancel_maps_to_failed(): void
    {
        $trx = Transaksi::factory()->create();
        $this->postJson('/webhooks/payment', $this->signedPayload($trx, 'cancel'))->assertOk();
        $this->assertSame(TransaksiStatus::Failed, $trx->fresh()->status);
    }

    public function test_unknown_order_is_404(): void
    {
        $payload = $this->signedPayload(Transaksi::factory()->create());
        $payload['order_id'] = 'GPN-20990101-XXXXXX';
        $payload['signature_key'] = Signature::make($payload['order_id'], '200', $payload['gross_amount'], (string) config('services.gpn_payment.server_key'));
        $this->postJson('/webhooks/payment', $payload)->assertNotFound();
    }

    public function test_expire_command_marks_stale_pending(): void // SRS 3.1.3.3 kedaluwarsa
    {
        $stale = Transaksi::factory()->create(['created_at' => now()->subHours(25), 'updated_at' => now()->subHours(25)]);
        $fresh = Transaksi::factory()->create();
        $this->artisan('transactions:expire')->assertSuccessful();
        $this->assertSame(TransaksiStatus::Expired, $stale->fresh()->status);
        $this->assertSame(TransaksiStatus::Pending, $fresh->fresh()->status);
    }

    public function test_iap_settlement_grants_iap_not_base_game(): void // legacy bug regression
    {
        $game = \App\Models\Game::factory()->published()->create();
        $iap = Iap::factory()->for($game)->create();
        $trx = Transaksi::factory()->create(['game_id' => $game->id, 'iap_id' => $iap->id, 'type' => 'iap']);
        $this->postJson('/webhooks/payment', $this->signedPayload($trx))->assertOk();
        $this->assertSame(1, IapOwnership::where('user_id', $trx->user_id)->where('iap_id', $iap->id)->count());
        $this->assertSame(0, Library::count());
    }
}
