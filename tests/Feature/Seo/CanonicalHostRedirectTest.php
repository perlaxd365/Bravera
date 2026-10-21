<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

class CanonicalHostRedirectTest extends TestCase
{
    public function test_www_and_http_requests_redirect_to_the_canonical_origin(): void
    {
        $this->get('http://www.brevare.com/nosotros?origen=seo')
            ->assertStatus(301)
            ->assertRedirect('https://brevare.com/nosotros?origen=seo');

        $this->get('http://brevare.com/')
            ->assertStatus(301)
            ->assertRedirect('https://brevare.com/');
    }

    public function test_canonical_storefront_request_is_not_redirected(): void
    {
        $this->get('https://brevare.com/up')
            ->assertOk();
    }

    public function test_other_hosts_are_not_redirected(): void
    {
        $this->get('http://localhost/up')
            ->assertOk();
    }
}
