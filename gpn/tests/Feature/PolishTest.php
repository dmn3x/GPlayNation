<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_intro_animation_shows_once_per_session(): void
    {
        Game::factory()->published()->create();
        $this->get('/')->assertSee('id="intro-overlay"', escape: false);
        $this->get('/')->assertDontSee('id="intro-overlay"', escape: false);
    }

    public function test_account_created_overlay_renders_after_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'A', 'email' => 'a@b.cd', 'password' => 'secret123',
            'password_confirmation' => 'secret123', 'account_type' => 'user', 'terms' => 'on',
        ]);
        $this->get(route('home'))->assertSee('Account created');
    }

    public function test_no_macos_chrome_anywhere(): void
    {
        Game::factory()->published()->create();
        foreach (['/', '/store', '/community', '/login', '/register'] as $url) {
            $this->get($url)->assertDontSee('traffic')->assertDontSee('titlebar');
        }
    }
}
