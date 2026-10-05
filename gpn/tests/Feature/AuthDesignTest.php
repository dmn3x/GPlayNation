<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthDesignTest extends TestCase
{
    public function test_register_matches_figma(): void
    {
        $r = $this->get('/register');
        $r->assertOk();
        $r->assertSee('w-[420px]', false);
        $r->assertSee('rounded-[17.5px]', false);
        $r->assertSee('h-[46px]', false);
        $r->assertSee('Join GPlayNation today', false);
        $r->assertSee('or continue with', false);
        $r->assertSee('Google', false);
        $r->assertSee('Apple', false);
        $r->assertSee('Already have an account?', false);
        $r->assertDontSee('w-[518px]', false);
        $r->assertDontSee('h-[48px]', false);
    }

    public function test_login_matches_figma(): void
    {
        $r = $this->get('/login');
        $r->assertOk();
        $r->assertSee('w-[518px]', false);
        $r->assertSee('card-auth-lg', false);
        $r->assertSee('input-auth-lg', false);
        $r->assertSee('btn-auth-lg', false);
        $r->assertSee('or continue with', false);
        $r->assertSee('Sign Up', false);
        $r->assertDontSee('w-[420px]', false);
        $r->assertDontSee('h-[48px]', false);
    }
}
