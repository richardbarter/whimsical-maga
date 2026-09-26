<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests assert on Inertia responses, not built assets, so don't require a Vite
     * manifest or dev server (CI's backend job never builds the frontend).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
