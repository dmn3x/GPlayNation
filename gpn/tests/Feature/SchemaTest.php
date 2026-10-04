<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\TransaksiStatus;
use App\Enums\UserStatus;
use App\Models\Developer;
use App\Models\Game;
use App\Models\KomisiTransaksi;
use App\Models\Library;
use App\Models\Promotion;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_developer_relation_and_role_casts(): void
    {
        $user = User::factory()->developer()->create();
        $this->assertTrue($user->isDeveloper());
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertInstanceOf(Developer::class, $user->developer);
        $this->assertFalse($user->isVerifiedDeveloper());
        $user->update(['status' => UserStatus::Active]);
        $this->assertTrue($user->fresh()->isVerifiedDeveloper());
    }

    public function test_game_relations_and_published_scope(): void
    {
        $game = Game::factory()->published()->create();
        Game::factory()->create(['status' => GameStatus::PendingReview]);
        $this->assertSame(1, Game::published()->count());
        $this->assertInstanceOf(Developer::class, $game->developer);
    }

    public function test_effective_price_applies_active_promotion_only(): void
    {
        $game = Game::factory()->published()->create(['price' => '200000.00']);
        $this->assertSame('200000.00', $game->effectivePrice());
        Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 25, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $this->assertSame('150000.00', $game->fresh()->effectivePrice());
        $this->assertInstanceOf(Promotion::class, $game->activePromotion());
    }

    public function test_library_unique_per_user_game(): void
    {
        $row = Library::factory()->create();
        $this->expectException(QueryException::class);
        Library::factory()->create(['user_id' => $row->user_id, 'game_id' => $row->game_id]);
    }

    public function test_order_id_format_and_uniqueness(): void
    {
        $id = Transaksi::generateOrderId();
        $this->assertMatchesRegularExpression('/^GPN-\d{8}-[A-Z0-9]{6}$/', $id);
        Transaksi::factory()->create(['order_id' => $id]);
        $this->assertNotSame($id, Transaksi::generateOrderId());
    }

    public function test_transaksi_is_final_only_for_terminal_states(): void
    {
        $pending = Transaksi::factory()->create(['status' => TransaksiStatus::Pending]);
        $success = Transaksi::factory()->success()->create();
        $this->assertFalse($pending->isFinal());
        $this->assertTrue($success->isFinal());
        $this->assertSame(0, KomisiTransaksi::count()); // table exists, untouched
    }
}
