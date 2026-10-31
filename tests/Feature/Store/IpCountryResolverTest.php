<?php

namespace Tests\Feature\Store;

use App\Support\Localization\IpCountryResolver;
use App\Support\Localization\StorefrontContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IpCountryResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_ip_country_detection_sets_currency_and_keeps_browser_language(): void
    {
        config(['cache.default' => 'array', 'storefront.geoip.enabled' => true]);
        Cache::store('array')->flush();
        Http::fake([
            'https://ipwho.is/8.8.8.8' => Http::response([
                'success' => true,
                'country_code' => 'CL',
            ]),
        ]);

        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '8.8.8.8');
        $request->headers->set('Accept-Language', 'es-CL,es;q=0.9');

        $context = StorefrontContext::fromRequest($request);

        $this->assertSame('CL', $context->countryCode);
        $this->assertSame('es', $context->locale);
        $this->assertSame('USD', $context->currency);
        Http::assertSentCount(1);

        // The second lookup must be served from cache.
        StorefrontContext::fromRequest($request);
        Http::assertSentCount(1);
    }

    public function test_private_ip_is_not_sent_to_the_geolocation_provider(): void
    {
        Http::fake();

        $this->assertNull(app(IpCountryResolver::class)->resolve('127.0.0.1'));
        $this->assertNull(app(IpCountryResolver::class)->resolve('192.168.1.10'));
        Http::assertNothingSent();
    }

    public function test_provider_failure_uses_language_fallback(): void
    {
        config(['cache.default' => 'array', 'storefront.geoip.enabled' => true]);
        Cache::store('array')->flush();
        Http::fake([
            'https://ipwho.is/1.1.1.1' => Http::response([], 503),
        ]);

        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '1.1.1.1');
        $request->headers->set('Accept-Language', 'en-US');

        $context = StorefrontContext::fromRequest($request);

        $this->assertSame('US', $context->countryCode);
        $this->assertSame('en', $context->locale);
        $this->assertSame('USD', $context->currency);
    }
}
