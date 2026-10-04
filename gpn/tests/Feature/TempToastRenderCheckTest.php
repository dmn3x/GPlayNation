<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempToastRenderCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_flashed_error_toast(): void
    {
        $dev = User::factory()->developer()->create(['status' => UserStatus::Pending]);
        $this->from('/login')->post('/login', ['email' => $dev->email, 'password' => 'password']);
        $this->get('/login')->assertSee('Your developer account is still awaiting admin verification.');
    }
}
