<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_hero_matches_figma(): void
    {
        Game::factory()->create(['status' => 'published']);
        $r = $this->get('/');
        $r->assertOk();
        $r->assertSee('Game of the Week', false);
        $r->assertSee('uppercase', false);
        $r->assertSee('text-[72px]', false);
        $r->assertSee('text-[27px]', false);
        $r->assertSee('rounded-3xl', false);
        $r->assertSee('shadow-[0_0_30px_rgba(29,78,216,0.4)]', false);
        $r->assertSee('Explore Now', false);
        $r->assertDontSee('h-[48px]', false);
    }
}
