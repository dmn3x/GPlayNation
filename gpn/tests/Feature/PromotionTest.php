<?php

namespace Tests\Feature;

use App\Models\{Game, Promotion, Transaksi, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private function devWithGame(): array
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        return [$dev, Game::factory()->for($dev->developer)->published()->create(['price' => '100000.00'])];
    }

    public function test_create_promotion_and_store_shows_discount(): void
    {
        [$dev, $game] = $this->devWithGame();
        $this->actingAs($dev)->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 30,
            'starts_at' => now()->subHour()->toDateTimeString(),
            'ends_at' => now()->addWeek()->toDateTimeString(),
        ])->assertRedirect(route('dev.promotion.index'));

        $this->assertSame('70000.00', $game->fresh()->effectivePrice());
        $this->get("/store/{$game->slug}")->assertSee('Rp 70.000')->assertSee('Rp 100.000');
    }

    public function test_overlapping_promotion_is_rejected(): void
    {
        [$dev, $game] = $this->devWithGame();
        Promotion::factory()->create(['game_id' => $game->id, 'starts_at' => now(), 'ends_at' => now()->addWeek()]);
        $this->actingAs($dev)->from('/dev/promotion')->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 10,
            'starts_at' => now()->addDays(3)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->toDateTimeString(),
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_overlapping_promotion_same_day_datetime_local_is_rejected(): void
    {
        [$dev, $game] = $this->devWithGame();
        Promotion::factory()->create([
            'game_id' => $game->id,
            'starts_at' => now()->startOfDay()->addHours(8),
            'ends_at' => now()->startOfDay()->addHours(18),
        ]);
        $shape = fn ($dt) => str_replace(' ', 'T', substr($dt, 0, 16));
        $this->actingAs($dev)->from('/dev/promotion')->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 10,
            'starts_at' => $shape(now()->startOfDay()->addHours(9)->toDateTimeString()),
            'ends_at' => $shape(now()->startOfDay()->addHours(20)->toDateTimeString()),
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_back_to_back_promotions_are_allowed(): void
    {
        [$dev, $game] = $this->devWithGame();
        Promotion::factory()->create([
            'game_id' => $game->id,
            'starts_at' => now()->startOfDay()->addHours(8),
            'ends_at' => now()->startOfDay()->addHours(18),
        ]);
        $shape = fn ($dt) => str_replace(' ', 'T', substr($dt, 0, 16));
        $this->actingAs($dev)->from('/dev/promotion')->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 10,
            'starts_at' => $shape(now()->startOfDay()->addHours(18)->toDateTimeString()),
            'ends_at' => $shape(now()->startOfDay()->addHours(20)->toDateTimeString()),
        ])->assertSessionHasNoErrors()->assertRedirect(route('dev.promotion.index'));

        $this->assertSame(2, Promotion::where('game_id', $game->id)->count());
    }

    public function test_expired_promotion_no_longer_applies(): void
    {
        [$dev, $game] = $this->devWithGame();
        Promotion::factory()->create(['game_id' => $game->id, 'discount_percent' => 50, 'starts_at' => now()->subWeeks(2), 'ends_at' => now()->subWeek()]);
        $this->assertSame('100000.00', $game->fresh()->effectivePrice());
    }

    public function test_promotion_requires_own_game(): void
    {
        [, $game] = $this->devWithGame();
        $other = User::factory()->verifiedDeveloper()->create();
        $this->actingAs($other)->post('/dev/promotion', [
            'game_id' => $game->id, 'discount_percent' => 10,
            'starts_at' => now()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString(),
        ])->assertForbidden();
    }

    public function test_tags_attach_and_detach(): void
    {
        $dev = User::factory()->verifiedDeveloper()->create();
        $game = Game::factory()->for($dev->developer)->published()->create();
        $this->actingAs($dev)->post('/dev/tags', ['name' => 'pixel-art'])->assertRedirect();
        $tag = \App\Models\Tag::sole();

        $this->actingAs($dev)->post("/dev/tags/{$tag->id}/attach", ['game_id' => $game->id]);
        $this->assertTrue($game->fresh()->tags->contains($tag));
        $this->actingAs($dev)->post("/dev/tags/{$tag->id}/detach", ['game_id' => $game->id]);
        $this->assertFalse($game->fresh()->tags->contains($tag));

        $this->get('/store?tag=pixel-art')->assertOk()->assertDontSee($game->name);
    }
}
