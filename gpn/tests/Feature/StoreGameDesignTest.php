<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreGameDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_grid_and_game_hero(): void
    {
        Game::factory()->count(3)->create(['status' => 'published']);
        $this->get('/store')
            ->assertOk()
            ->assertSee('Marketplace Home', false)
            ->assertSee('grid-cols-3', false)
            ->assertSee('Proudly Indonesian', false)
            ->assertDontSee('h-[48px]', false);

        $game = Game::first();
        $this->get('/store/'.$game->slug)
            ->assertOk()
            ->assertSee('Description', false)
            ->assertSee('Buy Now', false)
            ->assertSee('Add to Cart', false)
            ->assertSee('Complete your purchase', false)
            ->assertSee('Confirm Purchase', false)
            ->assertSee('Standard Edition', false);
    }
}
