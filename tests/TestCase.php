<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // This Laravel 12 install has no WithoutVite trait; InteractsWithContainer::withoutVite()
        // is the provided API — swaps Vite for a no-op so tests don't need public/build/manifest.json.
        $this->withoutVite();
    }
}
