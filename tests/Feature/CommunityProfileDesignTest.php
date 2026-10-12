<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityProfileDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_layout(): void
    {
        $this->get('/community')
            ->assertOk()
            ->assertSee('rounded-2xl', false)
            ->assertSee('TRENDING NOW', false)
            ->assertSee('UPCOMING EVENTS', false)
            ->assertSee('POPULAR COMMUNITIES', false)
            ->assertSee('w-[320px]', false)
            ->assertDontSee('h-[48px]', false);
    }

    public function test_profile_layout(): void
    {
        $user = User::factory()->create();
        \App\Models\Library::factory()->for($user)->create();
        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('My Library', false)
            ->assertSee('Purchased', false)
            ->assertSee('Wishlist', false)
            ->assertSee('rounded-2xl', false)
            ->assertSee('h-[410px]', false)
            ->assertSee('hours', false)
            ->assertDontSee('h-[48px]', false);
    }
}
