<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\Game;
use App\Models\Post;
use App\Models\PostVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_lists_posts_newest_first_with_real_score(): void
    {
        $old = Post::factory()->create(['body' => 'Old post', 'created_at' => now()->subDay()]);
        $new = Post::factory()->create(['body' => 'Fresh post', 'created_at' => now()]);
        PostVote::factory()->create(['post_id' => $old->id, 'value' => 1]);
        PostVote::factory()->create(['post_id' => $old->id, 'value' => 1]);

        $this->get('/community')
            ->assertOk()
            ->assertSeeInOrder(['Fresh post', 'Old post'])
            ->assertSee('2'); // score
    }

    public function test_posting_requires_auth_and_parses_hashtags(): void
    {
        $this->post('/community/posts', ['body' => 'hello #gamelokal'])->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->post('/community/posts', ['body' => 'Rilis baru! #gamelokal #HorrorID'])
            ->assertRedirect(route('community.index'));
        $post = Post::sole();
        $this->assertSame(['gamelokal', 'horrorid'], $post->hashtags);
    }

    public function test_vote_toggle_flip_and_remove(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->post("/posts/{$post->id}/vote", ['value' => 1]);
        $this->assertSame(1, $post->fresh()->score);

        $this->actingAs($user)->post("/posts/{$post->id}/vote", ['value' => -1]);
        $this->assertSame(-1, $post->fresh()->score);

        $this->actingAs($user)->post("/posts/{$post->id}/vote", ['value' => -1]);
        $this->assertSame(0, $post->fresh()->score);
        $this->assertSame(0, PostVote::count());
    }

    public function test_trending_counts_last_7_days_only(): void
    {
        // body must NOT contain the tag text — the feed renders bodies and would trip assertDontSee
        Post::factory()->create(['body' => 'old chatter', 'hashtags' => ['retro'], 'created_at' => now()->subDays(10)]);
        Post::factory()->create(['body' => 'b #gamelokal', 'hashtags' => ['gamelokal']]);

        $response = $this->get('/community');
        $response->assertSee('#gamelokal');
        // Figma shows the post's own hashtag pill on every card, so scope the
        // 7-day assertion to the TRENDING NOW rail instead of the whole page.
        $rail = \Illuminate\Support\Str::between($response->getContent(), 'TRENDING NOW', 'UPCOMING EVENTS');
        $this->assertStringContainsString('#gamelokal', $rail);
        $this->assertStringNotContainsString('#retro', $rail);
    }

    public function test_duplicate_vote_post_is_a_friendly_noop(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        PostVote::factory()->create(['post_id' => $post->id, 'user_id' => $user->id, 'value' => 1]);

        $this->actingAs($user)->from('/community')->post("/posts/{$post->id}/vote", ['value' => 1])
            ->assertRedirect('/community')
            ->assertSessionHas('success');
    }

    public function test_right_rail_shows_top_downloaded_this_month(): void
    {
        $hot = Game::factory()->published()->create(['name' => 'Viral Game']);
        $cold = Game::factory()->published()->create(['name' => 'Quiet Game']);
        Download::factory()->count(3)->create(['game_id' => $hot->id, 'created_at' => now()]);
        Download::factory()->create(['game_id' => $cold->id, 'created_at' => now()->subMonths(2)]);

        $this->get('/community')
            ->assertSee('Viral Game')
            ->assertDontSee('Quiet Game');
    }
}
