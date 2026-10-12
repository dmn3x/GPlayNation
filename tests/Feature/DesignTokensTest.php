<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    public function test_app_css_contains_figma_tokens(): void
    {
        $css = file_get_contents(base_path('resources/css/app.css'));
        foreach (['#0F0F10', '#161618', '#222224', '#27282A', '#1D4ED8', '#287BF7', '#60A5FA'] as $hex) {
            $this->assertStringContainsString($hex, $css, "missing token $hex");
        }
        $this->assertStringContainsString('.btn-primary', $css);
        $this->assertStringContainsString('rounded-[17.5px]', $css);
        $this->assertStringContainsString('rounded-[21.6px]', $css);
    }
}
