<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSortDropdownTest extends TestCase
{
    use RefreshDatabase;    public function test_sort_options_render_readable(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));
        $this->assertMatchesRegularExpression('/select\s+option\s*\{[^}]*background-color:\s*#161618/s', $css, 'options need an opaque background');
        $this->get('/store')
            ->assertOk()
            ->assertSee('name="sort"', false);
    }
}
