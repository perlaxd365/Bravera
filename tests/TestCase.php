<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Existing storefront tests describe the Peruvian experience. Tests
        // for another market override this header explicitly.
        app()->setLocale('es');
        $this->withHeader('CF-IPCountry', 'PE');
    }
}
