<?php

namespace Tests\Feature;

use Tests\TestCase;

class HeaderShellTest extends TestCase
{
    public function test_no_macos_chrome_and_header_specs(): void
    {
        $files = [
            base_path('resources/views/partials/marketing-header.blade.php'),
            base_path('resources/views/components/app-layout.blade.php'),
            base_path('resources/views/components/portal-layout.blade.php'),
        ];
        foreach ($files as $f) {
            $html = file_get_contents($f);
            $this->assertStringNotContainsString('h-[48px]', $html, "$f has chrome height");
            $this->assertDoesNotMatchRegularExpression('/FF453A.*FF9F0A|traffic/i', $html);
        }
        $m = file_get_contents($files[0]);
        $this->assertStringContainsString('h-[103px]', $m);
        $this->assertStringContainsString('max-w-[1510px]', $m);
        $this->assertStringContainsString('gap-12', $m);
        $this->assertStringContainsString('text-[21px]', $m);
        // Figma 209:252 hover = glass pill on nav
        $this->assertStringContainsString('hover:bg-white/10', $m);
        $this->assertStringContainsString('backdrop-blur-md', $m);
        $this->assertStringContainsString('rounded-full', $m);
        // Figma search pill 51px + 21px text, Sign In 51px ghost
        $this->assertStringContainsString('h-[51px]', $m);
        $this->assertStringContainsString('hover:bg-white/20', $m);
        $p = file_get_contents($files[2]);
        // Figma old dev/admin sidebar 208px (6:1976 etc.)
        $this->assertStringContainsString('w-[208px]', $p);
        $this->assertStringContainsString('h-[38px]', $p);
        $this->assertStringContainsString('hover:bg-white/5', $p);
    }
}
