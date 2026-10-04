<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'account_type' => 'user',
            'terms' => 'on',
        ], $overrides);
    }

    public function test_register_user_activates_immediately_and_logs_in(): void // SRS 3.1.1.1
    {
        $response = $this->post('/register', $this->validRegistration());
        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_register_developer_is_pending_and_cannot_log_in(): void // SRS 3.1.1.1 + 3.1.1.2 alternate
    {
        $response = $this->post('/register', $this->validRegistration([
            'account_type' => 'developer',
            'studio_name' => 'Studio Nusantara',
        ]));
        $this->assertGuest();
        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertSame('Studio Nusantara', $user->developer->studio_name);
        $response->assertRedirect(route('login'));

        $login = $this->post('/login', ['email' => 'budi@example.com', 'password' => 'secret123']);
        $this->assertGuest();
        $login->assertSessionHas('error');
    }

    public function test_developer_registration_requires_studio_name(): void
    {
        $this->from('/register')->post('/register', $this->validRegistration(['account_type' => 'developer']))
            ->assertSessionHasErrors('studio_name');
    }

    public function test_duplicate_email_is_rejected_inline(): void // SRS 3.1.1.1 alternate
    {
        User::factory()->create(['email' => 'budi@example.com']);
        $this->from('/register')->post('/register', $this->validRegistration())
            ->assertSessionHasErrors('email');
    }

    public function test_login_wrong_password_shows_error(): void // SRS 3.1.1.2 exception
    {
        User::factory()->create(['email' => 'budi@example.com']);
        $this->from('/login')->post('/login', ['email' => 'budi@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_role_redirects_after_login(): void // SRS F-02
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->verifiedDeveloper()->create();
        $user = User::factory()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/admin');
        auth()->logout();
        $this->post('/login', ['email' => $dev->email, 'password' => 'password'])->assertRedirect('/dev');
        auth()->logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('home'));
    }

    public function test_rejected_developer_login_shows_reason(): void
    {
        $dev = User::factory()->developer()->create(['status' => UserStatus::Rejected, 'rejection_reason' => 'Portfolio incomplete']);
        $this->from('/login')->post('/login', ['email' => $dev->email, 'password' => 'password'])
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Portfolio incomplete'));
    }

    public function test_role_middleware_blocks_wrong_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/dev')->assertForbidden();
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
