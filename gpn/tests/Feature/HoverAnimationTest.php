<?php

namespace Tests\Feature;

use Tests\TestCase;

class HoverAnimationTest extends TestCase
{
    public function test_hover_and_animation_classes_present(): void
    {
        $css = file_get_contents(base_path('resources/css/animations.css'));
        $this->assertStringContainsString('@media (prefers-reduced-motion', $css);
        $home = file_get_contents(base_path('resources/views/storefront/home.blade.php'));
        $this->assertStringContainsString('hover:brightness-110', $home);
        $this->assertStringContainsString('overlay-enter', $home);
        $card = file_get_contents(base_path('resources/views/components/game-card.blade.php'));
        $this->assertStringContainsString('hover:border-[#303034]', $card);
        $this->assertStringContainsString('group-hover:scale', $card);
    }
}
