<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_favicon_assets_exist_and_are_not_empty(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'favicon-192.png', 'apple-touch-icon.png', 'site.webmanifest'] as $file) {
            $path = public_path($file);

            $this->assertFileExists($path, "Falta el archivo public/{$file}");
            $this->assertGreaterThan(0, filesize($path), "public/{$file} está vacío");
        }
    }

    public function test_home_page_includes_favicon_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data:image/svg+xml;base64', false)
            ->assertSee('favicon.ico', false)
            ->assertSee('apple-touch-icon', false);
    }
}
