<?php

namespace Tests\Feature\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleTagManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_layout_loads_google_tag_manager_head_and_body_snippets(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('GTM-WDP4X795')
            ->assertSee('googletagmanager.com/gtm.js', false)
            ->assertSee('googletagmanager.com/ns.html', false)
            ->assertSee('G-WZRCWQBLXC')
            ->assertSee("gtag('config', 'G-WZRCWQBLXC')", false);
    }
}
