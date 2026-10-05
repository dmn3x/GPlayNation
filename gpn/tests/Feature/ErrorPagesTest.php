<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_is_themed(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found')->assertSee('GPlayNation');
    }

    public function test_403_page_is_themed(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden()->assertSee('Access denied');
    }

    public function test_expired_session_page_is_themed(): void
    {
        // VerifyCsrfToken is bypassed while testing (runningUnitTests), so a
        // mismatched token can't produce a real 419 here — assert the themed
        // page the exception handler renders for TokenMismatchException instead.
        $html = view('errors.419')->render();
        $this->assertStringContainsString('Session expired', $html);
        $this->assertStringContainsString('GPlayNation', $html);
    }
}
