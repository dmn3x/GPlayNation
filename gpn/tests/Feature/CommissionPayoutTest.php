<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Komisi;
use App\Models\KomisiTransaksi;
use App\Models\Payout;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_schedule_is_append_only_and_effective_dated(): void // SRS 3.1.4.2
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/commission', ['percentage' => '15.5', 'effective_from' => today()->addWeek()->toDateString()])
            ->assertRedirect();
        $this->assertSame(1, Komisi::count());
        // Settlement today still falls back to default 20% since 15.5% isn't effective yet
        $this->assertSame('20.00', app(CommissionService::class)->percentageFor(now()));
        $this->assertSame('15.50', app(CommissionService::class)->percentageFor(now()->addMonth()));
    }

    public function test_payout_sums_unpaid_and_stamps_rows(): void // SRS 3.1.4.3
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        foreach (['100000.00', '50000.00'] as $total) {
            $trx = Transaksi::factory()->success()->create(['game_id' => $game->id, 'total' => $total]);
            KomisiTransaksi::factory()->create([
                'transaksi_id' => $trx->id,
                'amount' => bcmul($total, '0.2', 2),
                'developer_amount' => bcmul($total, '0.8', 2),
            ]);
        }

        $this->actingAs($admin)->post("/admin/payouts/{$dev->developer->id}")->assertRedirect();

        $payout = Payout::sole();
        $this->assertSame('150000.00', $payout->gross);
        $this->assertSame('30000.00', $payout->commission);
        $this->assertSame('120000.00', $payout->net);
        $this->assertSame(0, KomisiTransaksi::whereNull('payout_id')->count());
        $this->assertSame('0', app(PayoutService::class)->unpaidBalance($dev->developer));
    }

    public function test_empty_payout_shows_message(): void // SRS 3.1.4.3 alternate
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $this->actingAs($admin)->post("/admin/payouts/{$dev->developer->id}")
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(0, Payout::count());
    }

    public function test_payouts_page_lists_balances_per_developer(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $trx = Transaksi::factory()->success()->create(['game_id' => $game->id]);
        KomisiTransaksi::factory()->create(['transaksi_id' => $trx->id, 'developer_amount' => '64000.00']);

        $this->actingAs($admin)->get('/admin/payouts')
            ->assertSee($dev->developer->studio_name)
            ->assertSee('Rp 64.000');
    }

    public function test_commission_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/commission')
            ->assertOk()
            ->assertSee('Current rate');
    }
}
