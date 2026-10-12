<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Iap;
use App\Models\IapOwnership;
use App\Models\Library;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_auth(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_profile_shows_stats_and_library(): void // SRS 4.1
    {
        $user = User::factory()->create();
        $game = Game::factory()->published()->create(['name' => 'Kuntilanak Mansion']);
        Library::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        Wishlist::factory()->create(['user_id' => $user->id]);
        IapOwnership::factory()->create(['user_id' => $user->id, 'iap_id' => Iap::factory()->for($game)]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Kuntilanak Mansion')
            ->assertSeeInOrder(['Assets', 'Slots', 'Stats']);
    }

    public function test_showcase_toggle_controls_profile_main_section(): void // SRS 4.1
    {
        $user = User::factory()->create();
        $library = Library::factory()->create(['user_id' => $user->id, 'is_showcased' => true]);
        $this->actingAs($user)->post("/profile/showcase/{$library->id}");
        $this->assertFalse($library->fresh()->is_showcased);
    }

    public function test_user_cannot_toggle_anothers_library(): void
    {
        $library = Library::factory()->create();
        $this->actingAs(User::factory()->create())->post("/profile/showcase/{$library->id}")->assertForbidden();
    }

    public function test_profile_update_name_and_password(): void // SRS 3.1.1.3
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $this->actingAs($user)->put('/profile', ['name' => 'New Name'])
            ->assertRedirect(route('profile.show'));
        $this->assertSame('New Name', $user->fresh()->name);

        $this->actingAs($user)->put('/profile', [
            'name' => 'New Name',
            'current_password' => 'password',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ]);
        $this->assertTrue(auth()->attempt(['email' => $user->email, 'password' => 'new-secret-1']));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/profile')->put('/profile', [
            'name' => $user->name,
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_avatar_upload_stores_on_public_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertRedirect(route('profile.show'));

        $path = $user->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }
}
