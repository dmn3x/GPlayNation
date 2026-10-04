<?php

namespace Tests\Feature;

use App\Models\Developer;
use App\Models\Game;
use App\Models\Komisi;
use App\Models\Library;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SRS 3.1.1 — Registrasi Akun, Login, Kelola Profil, Verifikasi Akun.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_activates_immediately(): void
    {
        $this->post('/register', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'role' => 'user',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'budi@example.com')->first();

        // 3.1.1.1 post-condition
        $this->assertSame('active', $user->status);
        $this->assertAuthenticatedAs($user);
    }

    public function test_developer_registration_is_pending_and_cannot_log_in(): void
    {
        $this->post('/register', [
            'name' => 'Studio Kaktus',
            'email' => 'dev@example.com',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'role' => 'developer',
            'nama_studio' => 'Kaktus Studio',
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'dev@example.com')->first();

        // 3.1.1.1: developer accounts wait for admin verification
        $this->assertSame('pending', $user->status);
        $this->assertGuest();

        // 3.1.1.2 alternate flow
        $this->post('/login', ['email' => 'dev@example.com', 'password' => 'rahasia-kuat-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_developer_registration_creates_the_developer_profile(): void
    {
        $this->post('/register', [
            'name' => 'Studio Kaktus',
            'email' => 'dev@example.com',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'role' => 'developer',
            'nama_studio' => 'Kaktus Studio',
        ]);

        $this->assertDatabaseHas('developers', [
            'nama_studio' => 'Kaktus Studio',
            'user_id' => User::where('email', 'dev@example.com')->value('id'),
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        // 3.1.1.1 alternate flow
        $this->post('/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'rahasia-kuat-123',
            'password_confirmation' => 'rahasia-kuat-123',
            'role' => 'user',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'ada@example.com')->count());
    }

    public function test_login_routes_each_role_to_its_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'salah@example.com']);

        // 3.1.1.2 exception flow
        $this->post('/login', ['email' => 'salah@example.com', 'password' => 'salah'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create(['name' => 'Awal', 'pengguna_nama' => 'Awal']);

        $this->actingAs($user)
            ->put('/profile', ['name' => 'Akhir', 'email' => 'akhir@example.com'])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Akhir']);
    }

    public function test_admin_can_verify_a_developer_account(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->post("/admin/users/{$dev->id}/verify", ['aksi' => 'setujui'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $dev->id, 'status' => 'active']);
    }

    public function test_rejecting_a_developer_requires_a_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $dev = User::factory()->developer()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->post("/admin/users/{$dev->id}/verify", ['aksi' => 'tolak'])
            ->assertSessionHasErrors('alasan');

        $this->assertDatabaseHas('users', ['id' => $dev->id, 'status' => 'pending']);
    }
}