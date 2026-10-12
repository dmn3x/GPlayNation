<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
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
        // VerifyCsrfToken is bypassed while testing (runningUnitTests), so drive a
        // TokenMismatchException through the real exception handler instead.
        $handler = $this->app->make(ExceptionHandler::class);
        $response = $handler->render($this->app->make('request'), new TokenMismatchException('CSRF token mismatch.'));

        $this->assertSame(419, $response->getStatusCode());
        $this->assertStringContainsString('Session expired', $response->getContent());
        $this->assertStringContainsString('GPlayNation', $response->getContent());
    }
}
