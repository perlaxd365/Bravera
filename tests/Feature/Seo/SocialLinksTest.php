<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_renders_only_configured_social_networks(): void
    {
        config([
            'seo.social_links' => [
                [
                    'network' => 'instagram',
                    'name' => 'Instagram',
                    'url' => 'https://www.instagram.com/brevare_peru/',
                ],
                [
                    'network' => 'facebook',
                    'name' => 'Facebook',
                    'url' => null,
                ],
                [
                    'network' => 'tiktok',
                    'name' => 'TikTok',
                    'url' => 'https://www.tiktok.com/@brevare.per',
                ],
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Síguenos')
            ->assertSee('https://www.instagram.com/brevare_peru/', false)
            ->assertSee('images/social/instagram.svg', false)
            ->assertSee('https://www.tiktok.com/@brevare.per', false)
            ->assertSee('images/social/tiktok.svg', false)
            ->assertDontSee('images/social/facebook.svg', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }
}
